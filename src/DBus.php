<?php

declare(strict_types=1);

namespace PhpUi;

/** A persistent, non-blocking session-bus connection. Variants are [signature, value]. */
final class DBus
{
    private static ?self $shared = null;
    private const BUS = 'org.freedesktop.DBus';
    private const PATH = '/org/freedesktop/DBus';
    private const LIMIT = 16777216;
    public string $name = '';
    private $socket;
    private string $in = '', $out = '';
    private int $serial = 0, $subscription = 0;
    private array $calls = [], $listeners = [];
    private array $owners = [];

    public static function session(): ?DBus
    {
        if (self::$shared) return self::$shared;
        $bus = null;
        try {
            $address = explode(';', getenv('DBUS_SESSION_BUS_ADDRESS') ?: 'unix:path=' . (getenv('XDG_RUNTIME_DIR') ?: '/run/user/' . posix_geteuid()) . '/bus')[0];
            if (!str_starts_with($address, 'unix:')) return null;
            $options = [];
            foreach (explode(',', substr($address, 5)) as $pair) {
                [$key, $value] = array_pad(explode('=', $pair, 2), 2, '');
                $options[$key] = rawurldecode($value);
            }
            $path = $options['path'] ?? (isset($options['abstract']) ? "\0" . $options['abstract'] : null);
            if ($path === null) return null;
            $socket = @stream_socket_client('unix://' . $path, $errno, $error, 1);
            if (!$socket) return null;
            $bus = new self();
            $bus->socket = $socket;
            stream_set_timeout($socket, 1);
            fwrite($socket, "\0AUTH EXTERNAL " . bin2hex((string) posix_geteuid()) . "\r\n");
            $auth = fgets($socket, 1024);
            if ($auth === false || !str_starts_with($auth, 'OK ')) { $bus->close(); return null; }
            fwrite($socket, "BEGIN\r\n");
            stream_set_blocking($socket, false);
            $bus->call(self::BUS, self::PATH, self::BUS, 'Hello', done: function ($result) use ($bus) {
                $bus->name = $result[0] ?? '';
            }, timeout: 1);
            $end = microtime(true) + 1;
            do {
                $bus->poll();
                if ($bus->name !== '') return self::$shared = $bus;
                usleep(1000);
            } while ($bus->socket && microtime(true) < $end);
        } catch (\Throwable) {
            // A missing or unusable session bus is an expected desktop fallback.
        }
        $bus?->close();
        return null;
    }

    public function call(string $dest, string $path, string $iface, string $member,
        string $signature = '', array $args = [], ?callable $done = null, float $timeout = 3.0): void
    {
        $serial = $this->message(1, [1 => ['o', $path], 2 => ['s', $iface], 3 => ['s', $member], 6 => ['s', $dest]], $signature, $args);
        $this->calls[$serial] = [$done, microtime(true) + $timeout];
    }

    public function on(string $iface, string $member, callable $fn, ?string $path = null, ?string $sender = null): int
    {
        $rule = "type='signal',interface='$iface',member='$member'";
        if ($path !== null) $rule .= ",path='$path'";
        if ($sender !== null) {
            $rule .= ",sender='$sender'";
            if (!str_starts_with($sender, ':') && !array_key_exists($sender, $this->owners)) {
                $this->owners[$sender] = null;
                $this->call(self::BUS, self::PATH, self::BUS, 'AddMatch', 's', ["type='signal',sender='org.freedesktop.DBus',interface='org.freedesktop.DBus',member='NameOwnerChanged',arg0='$sender'"]);
                $this->call(self::BUS, self::PATH, self::BUS, 'GetNameOwner', 's', [$sender], function ($result) use ($sender) {
                    $this->owners[$sender] = $result[0] ?? null;
                });
            }
        }
        $id = ++$this->subscription;
        $this->listeners[$id] = [$iface, $member, $fn, $path, $sender, $rule];
        $this->call(self::BUS, self::PATH, self::BUS, 'AddMatch', 's', [$rule]);
        return $id;
    }

    public function off(int $id): void
    {
        if (!isset($this->listeners[$id])) return;
        $rule = $this->listeners[$id][5];
        unset($this->listeners[$id]);
        $this->call(self::BUS, self::PATH, self::BUS, 'RemoveMatch', 's', [$rule]);
    }

    public function pending(): int { return count($this->calls); }
    /** @internal Lets services abandon signal-only requests when the bus goes away. */
    public function connected(): bool { return is_resource($this->socket); }

    public function poll(): bool
    {
        $ran = false;
        if (!$this->socket) return $this->disconnect();
        if ($this->out !== '') {
            $written = @fwrite($this->socket, $this->out);
            if ($written === false) return $this->disconnect();
            $this->out = substr($this->out, $written);
        }
        // Bound I/O per turn; a busy bus must not starve the UI.
        $bytes = @fread($this->socket, 65536);
        if ($bytes === false || feof($this->socket)) return $this->disconnect();
        $this->in .= $bytes;
        $messages = [];
        try {
            for ($i = 0; $i < 64 && strlen($this->in) >= 16; $i++) {
                $fixed = unpack('Cendian/Ctype/Cflags/Cversion/Vbody/Vserial/Vheaders', substr($this->in, 0, 16));
                if ($fixed['endian'] !== 108 || $fixed['version'] !== 1 || !in_array($fixed['type'], [1, 2, 3, 4], true) || !$fixed['serial']) throw new \UnexpectedValueException('Malformed header');
                $bodyAt = (16 + $fixed['headers'] + 7) & ~7;
                $length = $bodyAt + $fixed['body'];
                if ($length > self::LIMIT) throw new \UnexpectedValueException('Message too large');
                if (strlen($this->in) < $length) break;
                $headers = [];
                $data = pack('V', $fixed['headers']) . "\0\0\0\0" . substr($this->in, 16, $fixed['headers']);
                foreach (self::unpack('a(yv)', $data)[0] as [$field, $variant]) {
                    if (isset($headers[$field])) throw new \UnexpectedValueException('Duplicate header');
                    $expected = [1 => 'o', 2 => 's', 3 => 's', 4 => 's', 5 => 'u', 6 => 's', 7 => 's', 8 => 'g', 9 => 'u'];
                    if (isset($expected[$field]) && $variant[0] !== $expected[$field]) throw new \UnexpectedValueException('Invalid header type');
                    $headers[$field] = $variant[1];
                }
                $required = match ($fixed['type']) { 1 => [1, 3], 2 => [5], 3 => [4, 5], 4 => [1, 2, 3] };
                foreach ($required as $field) if (!isset($headers[$field])) throw new \UnexpectedValueException('Missing header field');
                if (!empty($headers[9])) throw new \UnexpectedValueException('Unix FD transport is not supported');
                $args = self::unpack($headers[8] ?? '', substr($this->in, $bodyAt, $fixed['body']));
                $messages[] = [$fixed, $headers, $args];
                $this->in = substr($this->in, $length);
            }
        } catch (\Throwable) { return $this->disconnect(); }
        foreach ($messages as [$fixed, $headers, $args]) {
            $type = $fixed['type'];
            if ($type === 2 || $type === 3) {
                $reply = $headers[5] ?? 0;
                if (isset($this->calls[$reply])) {
                    [$done] = $this->calls[$reply];
                    unset($this->calls[$reply]);
                    if ($done) { $ran = true; $done($type === 2 ? $args : null, $type === 3 ? ($headers[4] ?? 'error') . ': ' . ($args[0] ?? '') : null); }
                }
            } elseif ($type === 4) {
                if (($headers[7] ?? '') === self::BUS && ($headers[2] ?? '') === self::BUS
                    && ($headers[3] ?? '') === 'NameOwnerChanged' && array_key_exists($args[0] ?? '', $this->owners)) {
                    $this->owners[$args[0]] = $args[2] ?: null;
                }
                foreach ($this->listeners as [$iface, $member, $fn, $path, $sender]) {
                    if (($headers[2] ?? '') === $iface && ($headers[3] ?? '') === $member
                        && ($path === null || ($headers[1] ?? '') === $path)
                        && ($sender === null || ($headers[7] ?? '') === $sender || ($headers[7] ?? '') === ($this->owners[$sender] ?? null))) {
                        $ran = true;
                        $fn($args, $headers[1] ?? '');
                    }
                }
            } elseif (isset($headers[7]) && !($fixed['flags'] & 1)) {
                $this->message(3, [4 => ['s', 'org.freedesktop.DBus.Error.UnknownMethod'], 5 => ['u', $fixed['serial']], 6 => ['s', $headers[7]]], 's', ['No exported methods']);
            }
        }
        foreach ($this->calls as $serial => [$done, $deadline]) {
            if (microtime(true) < $deadline) continue;
            unset($this->calls[$serial]);
            if ($done) { $ran = true; $done(null, 'timeout'); }
        }
        return $ran;
    }

    private function disconnect(): bool
    {
        if (is_resource($this->socket)) fclose($this->socket);
        $this->socket = null;
        $this->in = $this->out = '';
        if (self::$shared === $this) self::$shared = null;
        $calls = $this->calls;
        $this->calls = [];
        $ran = false;
        foreach ($calls as [$done]) if ($done) { $ran = true; $done(null, 'disconnected'); }
        return $ran;
    }

    public function close(): void { $this->disconnect(); $this->listeners = []; }

    private function message(int $type, array $fields, string $signature, array $args): int
    {
        $body = self::pack($signature, $args);
        if ($signature !== '') $fields[8] = ['g', $signature];
        $list = [];
        foreach ($fields as $key => $value) $list[] = [$key, $value];
        $headers = substr(self::pack('a(yv)', [$list]), 8);
        $serial = ++$this->serial;
        $message = pack('CCCCVVV', 108, $type, 0, 1, strlen($body), $serial, strlen($headers)) . $headers;
        $message .= str_repeat("\0", (-strlen($message)) & 7) . $body;
        if (strlen($message) > self::LIMIT) throw new \InvalidArgumentException('Message too large');
        $this->out .= $message;
        return $serial;
    }

    // Wire values --------------------------------------------------------

    private static function types(string $signature): array
    {
        if (strlen($signature) > 255) throw new \InvalidArgumentException('Signature too long');
        $at = 0; $types = [];
        while ($at < strlen($signature)) $types[] = self::type($signature, $at, 0);
        return $types;
    }

    private static function type(string $signature, int &$at, int $depth): string
    {
        if ($depth > 32 || $at >= strlen($signature)) throw new \InvalidArgumentException('Invalid signature');
        $start = $at;
        $char = $signature[$at++];
        if ($char === 'a') self::type($signature, $at, $depth + 1);
        elseif ($char === '(' || $char === '{') {
            $end = $char === '(' ? ')' : '}';
            while (($signature[$at] ?? '') !== $end) self::type($signature, $at, $depth + 1);
            $at++;
        } elseif (!str_contains('ybnqiuxtdsogv', $char)) throw new \InvalidArgumentException('Unsupported D-Bus type');
        return substr($signature, $start, $at - $start);
    }

    private static function alignment(string $type): int
    {
        return match ($type[0]) { 'y', 'g', 'v' => 1, 'n', 'q' => 2, 'x', 't', 'd', '(', '{' => 8, default => 4 };
    }

    public static function pack(string $signature, array $values): string
    {
        $types = self::types($signature);
        if (count($types) !== count($values)) throw new \InvalidArgumentException('Signature/value count mismatch');
        $out = '';
        foreach ($types as $i => $type) self::encode($type, $values[$i], $out);
        return $out;
    }

    private static function encode(string $type, mixed $value, string &$out): void
    {
        $out .= str_repeat("\0", (-strlen($out)) & (self::alignment($type) - 1));
        $char = $type[0];
        if ($char === 'a') {
            $lengthAt = strlen($out); $out .= "\0\0\0\0";
            $element = substr($type, 1);
            $out .= str_repeat("\0", (-strlen($out)) & (self::alignment($element) - 1));
            $start = strlen($out);
            foreach ($value as $key => $item) self::encode($element, $element[0] === '{' ? [$key, $item] : $item, $out);
            $out = substr_replace($out, pack('V', strlen($out) - $start), $lengthAt, 4);
        } elseif ($char === '(' || $char === '{') {
            $types = self::types(substr($type, 1, -1));
            if (count($types) !== count($value)) throw new \InvalidArgumentException('Struct/value count mismatch');
            foreach ($types as $i => $child) self::encode($child, $value[$i], $out);
        } elseif ($char === 'v') {
            if (count(self::types($value[0])) !== 1) throw new \InvalidArgumentException('Variant requires one type');
            self::encode('g', $value[0], $out);
            self::encode($value[0], $value[1], $out);
        } elseif (str_contains('sog', $char)) {
            if (str_contains($value, "\0") || ($char === 'g' && strlen($value) > 255)) throw new \InvalidArgumentException('Invalid D-Bus string');
            $out .= pack($char === 'g' ? 'C' : 'V', strlen($value)) . $value . "\0";
        } else {
            $out .= match ($char) {
                'y' => pack('C', $value), 'b' => pack('V', $value ? 1 : 0),
                'n', 'q' => pack('v', $value), 'i', 'u' => pack('V', $value),
                'x', 't' => pack('P', $value), 'd' => pack('e', $value),
            };
        }
    }

    public static function unpack(string $signature, string $bytes): array
    {
        $at = 0; $values = [];
        foreach (self::types($signature) as $type) $values[] = self::decode($type, $bytes, $at);
        if ($at !== strlen($bytes)) throw new \UnexpectedValueException('Trailing D-Bus data');
        return $values;
    }

    private static function take(string $bytes, int &$at, int $length): string
    {
        if ($length < 0 || $at + $length > strlen($bytes)) throw new \UnexpectedValueException('Truncated D-Bus value');
        $out = substr($bytes, $at, $length); $at += $length;
        return $out;
    }

    private static function decode(string $type, string $bytes, int &$at): mixed
    {
        self::take($bytes, $at, (-$at) & (self::alignment($type) - 1));
        $char = $type[0];
        if ($char === 'a') {
            $length = unpack('V', self::take($bytes, $at, 4))[1];
            $element = substr($type, 1);
            self::take($bytes, $at, (-$at) & (self::alignment($element) - 1));
            $end = $at + $length; $out = [];
            if ($end > strlen($bytes)) throw new \UnexpectedValueException('Truncated array');
            while ($at < $end) {
                $before = $at;
                $value = self::decode($element, $bytes, $at);
                if ($at <= $before || $at > $end) throw new \UnexpectedValueException('Invalid array length');
                if ($element[0] === '{') $out[$value[0]] = $value[1]; else $out[] = $value;
            }
            return $out;
        }
        if ($char === '(' || $char === '{') {
            $out = [];
            foreach (self::types(substr($type, 1, -1)) as $child) $out[] = self::decode($child, $bytes, $at);
            return $out;
        }
        if ($char === 'v') {
            $signature = self::decode('g', $bytes, $at);
            if (count(self::types($signature)) !== 1) throw new \UnexpectedValueException('Invalid variant');
            return [$signature, self::decode($signature, $bytes, $at)];
        }
        if (str_contains('sog', $char)) {
            $length = unpack($char === 'g' ? 'C' : 'V', self::take($bytes, $at, $char === 'g' ? 1 : 4))[1];
            $value = self::take($bytes, $at, $length);
            if (self::take($bytes, $at, 1) !== "\0" || str_contains($value, "\0")) throw new \UnexpectedValueException('Invalid string');
            return $value;
        }
        [$format, $size] = match ($char) {
            'y' => ['C', 1], 'n', 'q' => ['v', 2], 'b', 'i', 'u' => ['V', 4], 'x', 't' => ['P', 8], 'd' => ['e', 8],
        };
        $value = unpack($format, self::take($bytes, $at, $size))[1];
        return match ($char) {
            'b' => match ($value) { 0 => false, 1 => true, default => throw new \UnexpectedValueException('Invalid boolean') },
            'n' => $value >= 32768 ? $value - 65536 : $value,
            'i' => $value >= 2147483648 ? $value - 4294967296 : $value,
            default => $value,
        };
    }
}

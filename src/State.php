<?php

declare(strict_types=1);

namespace PhpUi;

/**
 * The untyped store state() returns for an array: `$s->count++`, `$s->todos[] = $todo`.
 * Changes made outside an event handler redraw the window too. For autocompletion,
 * pass state() an object instead.
 */
final class State implements \IteratorAggregate, \Countable
{
    /** @var array<string, object> State created with a key, kept for the whole process. */
    private static array $keyed = [];
    /** @var object[] State the app's script created, in order, until its loop started (for hot reload). */
    private static array $created = [];
    /** @var object[] The previous run's, while hot reload runs the script again. */
    private static array $previous = [];
    private static bool $recording = true;

    /** @internal state() registers what the script creates, so hot reload can keep its values. */
    public static function track(object $state): object
    {
        if (!self::$recording) return $state;
        $old = self::$previous[count(self::$created)] ?? null;
        if ($old !== null) self::carry($old, $state);
        self::$created[] = $state;
        return $state;
    }

    /** @internal The app's loop started: state() calls from now on are not the script's. */
    public static function stopRecording(): void
    {
        self::$recording = false;
    }

    /** @internal */
    public static function beginReload(): void
    {
        [self::$previous, self::$created, self::$recording] = [self::$created, [], true];
    }

    /** @internal The reload failed: the previous run's state stays. */
    public static function cancelReload(): void
    {
        self::$created = self::$previous;
    }

    /** @internal */
    public static function endReload(): void
    {
        [self::$previous, self::$recording] = [[], false];
    }

    /**
     * The values of $old in $new, the same state in the new code: keys or properties
     * both have, where the value still fits the property's type.
     */
    private static function carry(object $old, object $new): void
    {
        if ($old instanceof self && $new instanceof self) {
            foreach ($new->data as $k => $_) if (array_key_exists($k, $old->data)) $new->data[$k] = $old->data[$k];
            return;
        }
        if ($old instanceof self || $new instanceof self) return;
        foreach ((new \ReflectionObject($new))->getProperties(\ReflectionProperty::IS_PUBLIC) as $p) {
            if ($p->isStatic() || $p->isReadOnly()) continue;
            $name = $p->getName();
            if (!property_exists($old, $name)) continue;
            $from = new \ReflectionProperty($old, $name);
            if ($from->isStatic() || !$from->isPublic() || !$from->isInitialized($old)) continue;
            try {
                $new->$name = $old->$name;
            } catch (\TypeError) {
                // The property's type changed: it starts from its new initial value.
            }
        }
    }

    public function __construct(private array $data = []) {}

    /** The state for $key, created from $initial the first time. */
    public static function keyed(string $key, object|array $initial): object
    {
        return self::$keyed[$key] ??= is_array($initial) ? new self($initial) : $initial;
    }

    /** By reference, so `$s->list[] = $x` and `$s->map['k'] = $v` change the store. */
    public function &__get(string $name): mixed
    {
        if (!array_key_exists($name, $this->data)) {
            trigger_error("Undefined state key \"$name\"", E_USER_WARNING);
            $this->data[$name] = null;
        }
        // Arrays and objects can be changed in place without __set: redraw to be safe.
        if (is_array($this->data[$name]) || is_object($this->data[$name])) self::changed();
        return $this->data[$name];
    }

    public function __set(string $name, mixed $value): void
    {
        $this->data[$name] = $value;
        self::changed();
    }

    public function __isset(string $name): bool
    {
        return isset($this->data[$name]);
    }

    public function __unset(string $name): void
    {
        unset($this->data[$name]);
        self::changed();
    }

    /** Set several keys at once. */
    public function set(array $values): self
    {
        $this->data = $values + $this->data;
        self::changed();
        return $this;
    }

    /** Change state in a callback, e.g. from code that does not run in an event handler. */
    public function update(callable $fn): self
    {
        $fn($this);
        self::changed();
        return $this;
    }

    public function has(string $name): bool
    {
        return array_key_exists($name, $this->data);
    }

    public function toArray(): array
    {
        return $this->data;
    }

    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->data);
    }

    public function count(): int
    {
        return count($this->data);
    }

    private static function changed(): void
    {
        App::current()?->invalidate();
    }
}

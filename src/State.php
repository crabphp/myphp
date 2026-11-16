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

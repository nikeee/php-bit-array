<?php

namespace Nikeee\BitArray;

abstract class BitArray
{
    public abstract function toRawString(): string;

    public abstract function get(int $index): bool;

    public abstract function set(int $index, bool $value): self;

    public abstract function at(int $index): bool;

    public abstract function clear(): self;

    public abstract function fill(bool $value): self;

    public abstract function popCount(bool $value = true): int;

    public abstract function __serialize(): array;

    public abstract function __unserialize(array $data): void;
}

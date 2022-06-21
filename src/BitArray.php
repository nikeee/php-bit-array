<?php

namespace Nikeee\BitArray;

abstract class BitArray
{
    abstract function toRawString(): string;

    abstract function get(int $index): bool;

    abstract function set(int $index, bool $value): self;

    abstract function at(int $index): bool;

    abstract function clear(): self;

    abstract function fill(bool $value): self;

    abstract function popCount(bool $value = true): int;

    abstract function __serialize(): array;

    abstract function __unserialize(array $data): void;

    function applyBitwiseNot(): void
    {
        // Slow fallback implementation in case the user passed an array which is not the same type
        // (called by the child class if needed)
        for ($i = 0; $i < $this->numberOfBits; ++$i) {
            $v0 = $this->get($i);
            $this->set($i, !$v0);
        }
    }

    function applyBitwiseAnd(BitArray $other): void
    {
        if ($this->numberOfBits !== $other->numberOfBits)
            throw new \InvalidArgumentException('Both BitArrays must have the same length');

        // Slow fallback implementation in case the user passed an array which is not the same type
        // (called by the child class if needed)
        for ($i = 0; $i < $this->numberOfBits; ++$i) {
            $v0 = $this->get($i);
            $v1 = $other->get($i);
            $this->set($i, $v0 & $v1);
        }
    }

    function applyBitwiseOr(BitArray $other): void
    {
        if ($this->numberOfBits !== $other->numberOfBits)
            throw new \InvalidArgumentException('Both BitArrays must have the same length');

        // Slow fallback implementation in case the user passed an array which is not the same type
        // (called by the child class if needed)
        for ($i = 0; $i < $this->numberOfBits; ++$i) {
            $v0 = $this->get($i);
            $v1 = $other->get($i);
            $this->set($i, $v0 | $v1);
        }
    }

    function applyBitwiseXor(BitArray $other): void
    {
        if ($this->numberOfBits !== $other->numberOfBits)
            throw new \InvalidArgumentException('Both BitArrays must have the same length');

        // Slow fallback implementation in case the user passed an array which is not the same type
        // (called by the child class)
        for ($i = 0; $i < $this->numberOfBits; ++$i) {
            $v0 = $this->get($i);
            $v1 = $other->get($i);
            $this->set($i, ($v0 ^ $v1) & 0xff);
        }
    }

    static function fromRawString(string $rawString): BitArray
    {
        if (extension_loaded('gmp')) {
            return GmpBitArray::fromRawString($rawString);
        }
        return PhpBitArray::fromRawString($rawString);
    }
}

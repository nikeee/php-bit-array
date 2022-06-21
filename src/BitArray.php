<?php

namespace Nikeee\BitArray;

use InvalidArgumentException;

abstract class BitArray
{
    /** @readonly */
    protected int $numberOfBits;

    function getNumberOfBits(): int
    {
        return $this->numberOfBits;
    }

    function __construct(int $numberOfBits)
    {
        if ($numberOfBits <= 0 || ($numberOfBits % 8) !== 0)
            throw new InvalidArgumentException('$numberOfBits must be a multiple of 8 and greater than 0');
        $this->numberOfBits = $numberOfBits;
    }

    abstract function toRawString(): string;

    abstract function get(int $index): bool;

    abstract function set(int $index, bool $value): self;

    function at(int $index): bool
    {
        $positiveIndex = $index < 0
            ? $this->numberOfBits + $index // JS's Array.at does not handle wrap-around (`[0].at(-10)`), so we also don't do it
            : $index;
        return $this->get($positiveIndex);
    }

    abstract function clear(): self;

    abstract function fill(bool $value): self;

    abstract function popCount(bool $value = true): int;

    function collectIndicesWithValue(bool $needleValue): array
    {
        $res = [];

        // Slow fallback implementation in case there is no faster, specific implementation
        // (called by the child class if needed)
        for ($i = 0; $i < $this->numberOfBits; ++$i) {
            if ($this->get($i) === $needleValue)
                $res[] = $i;
        }
        return $res;
    }

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

    abstract function __serialize(): array;

    abstract function __unserialize(array $data): void;

    static function fromRawString(string $rawString): BitArray
    {
        return extension_loaded('gmp')
            ? GmpBitArray::fromRawString($rawString)
            : PhpBitArray::fromRawString($rawString);
    }

    static function create(int $numberOfBits): BitArray
    {
        if ($numberOfBits <= 0 || ($numberOfBits % 8) !== 0)
            throw new InvalidArgumentException('$numberOfBits must be a multiple of 8 and greater than 0');

        return extension_loaded('gmp')
            ? GmpBitArray::create($numberOfBits)
            : PhpBitArray::create($numberOfBits);
    }
}

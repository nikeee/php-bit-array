<?php

namespace Nikeee\BitArray;

use InvalidArgumentException;

/**
 * Represents a fixed-size array that holds bit values. Size must be a multiple of 8.
 */
abstract class BitArray
{
    /** @readonly */
    protected int $numberOfBits;

    /**
     * Number of bits present in this {@link BitArray}.
     *
     * Time complexity: O(1)
     */
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

    /**
     * Sets a value of the array.
     * @param $index int An integer `n` that is `0 <= n < this.length`.
     * @param $value bool The value to safe. Can be `true | false | 0 | 1`.
     * @returns self Reference to the same {@link BitArray}, so multiple calls can be chained.
     *
     * Time complexity: O(1)
     */
    abstract function set(int $index, bool $value): self;

    /**
     * Gets a value of the array.
     *
     * Time complexity: O(1)
     *
     * @param $index int An integer `n` that is `0 <= n < this.length`.
     * @returns bool value that indicates whether the bit was set.
     */
    abstract function get(int $index): bool;

    /**
     * Like {@link BitArray::get}, but can handle negative indices like `Array.at` in JavaScript.
     *
     * Time complexity: O(1)
     */
    function at(int $index): bool
    {
        $positiveIndex = $index < 0
            ? $this->numberOfBits + $index // JS's Array.at does not handle wrap-around (`[0].at(-10)`), so we also don't do it
            : $index;
        return $this->get($positiveIndex);
    }

    /**
     * Sets all `{@link bool}`s in the array to `false`. Keeps the array length.
     *
     * Time complexity: O(n) with n being the size of the array
     *
     * @returns self Reference to the same {@link BitArray}, so multiple calls can be chained.
     */
    abstract function clear(): self;

    /**
     * Sets all `{@link bool}`s in the array to `value`. Keeps the array length.
     * @returns self Reference to the same {@link BitArray}, so multiple calls can be chained.
     */
    abstract function fill(bool $value): self;

    /**
     * Counts the number of bits set to a specific {@link $needleValue}.
     *
     * Time complexity: O(n) with n being the size of the array
     * @returns int The number of bits set to a specific {@link $value}.
     */
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

    /**
     * Performs a bitwise NOT (`!`) on every bit of the array. Mutates the array.
     *
     * Time complexity: O(n) with n being the size of the arrays
     */
    function applyBitwiseNot(): void
    {
        // Slow fallback implementation in case the user passed an array which is not the same type
        // (called by the child class if needed)
        for ($i = 0; $i < $this->numberOfBits; ++$i) {
            $v0 = $this->get($i);
            $this->set($i, !$v0);
        }
    }

    /**
     * Performs a bitwise AND (`&`) of two arrays. Mutates the array.
     * Performs faster if {@link $other} is of the same type as {@link $this}.
     *
     * Time complexity: O(n) with n being the size of the arrays
     *
     * @param $other self The other {@link BitArray}
     */
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

    /**
     * Performs a bitwise OR (`|`) of two arrays. Mutates the array.
     * Performs faster if {@link $other} is of the same type as {@link $this}.
     *
     * Time complexity: O(n) with n being the size of the arrays
     *
     * @param $other self The other {@link BitArray}
     */
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

    /**
     * Performs a bitwise XOR (`^`) of two arrays. Mutates the array.
     * Performs faster if {@link $other} is of the same type as {@link $this}.
     *
     * Time complexity: O(n) with n being the size of the arrays
     *
     * @param $other self The other {@link BitArray}
     */
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

    /**
     * Clones the array into a new one. Modifications made on the clone won't be reflected in the source array.
     *
     * Time complexity: O(n) with n being the size of the arrays.
     * @return BitArray
     */
    abstract function clone(): BitArray;

    /**
     * Returns rhe string representation of the bit array. Consists of 1's and 0's.
     * Its length is equal to the return value of {@link BitArray::getNumberOfBits()}.
     *
     * Time complexity: O(n) with n being the size of the arrays
     *
     * @return string The string representation of the bit array.
     */
    function toBitString(): string
    {
        // Slow fallback implementation in case the user passed an array which is not the same type
        // (called by the child class if needed)
        $result = '';
        for ($i = 0; $i < $this->numberOfBits; ++$i) {
            $result .= $this->get($i) ? '1' : '0';
        }
        return $result;
    }

    abstract function __serialize(): array;

    abstract function __unserialize(array $data): void;

    /**
     * Creates an instance of {@link BitArray} by providing the underlying data.
     * Example usage:
     * ```php
     * $a = BitArray::create(100);
     * // ...
     * $buffer = a->toRawString();
     * // save buffer for later
     * // ...
     * $b = BitArray::fromRawString($buffer);
     * ```
     * @param $rawString string Source buffer
     * @returns self New {@link BitArray}
     */
    static function fromRawString(string $rawString): self
    {
        return PhpBitArray::fromRawString($rawString);
        // return extension_loaded('gmp')
        //     ? GmpBitArray::fromRawString($rawString)
        //     : PhpBitArray::fromRawString($rawString);
    }

    /**
     * Creates an empty instance of {@link BitArray} with a specific size.
     * @param $numberOfBits int The number of bits.
     * @returns self New {@link BitArray}
     */
    static function create(int $numberOfBits): self
    {
        if ($numberOfBits <= 0 || ($numberOfBits % 8) !== 0)
            throw new InvalidArgumentException('$numberOfBits must be a multiple of 8 and greater than 0');

        return PhpBitArray::create($numberOfBits);
        // return extension_loaded('gmp')
        //    ? GmpBitArray::create($numberOfBits)
        //    : PhpBitArray::create($numberOfBits);
    }
}

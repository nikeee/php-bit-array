<?php

namespace Nikeee\BitArray;

use InvalidArgumentException;
use OutOfBoundsException;

class PhpBitArray extends BitArray
{
    /**
     * The buffer is filled with 1s and 0s
     * For example, index 0 will be mapped to the left-most bit (MSB) of the first byte:
     *     0b10000000
     * Index 1 will be mapped to:
     *     0b01000000
     * So when having 16 slots and setting bit 0 and 9, the buffer would look like this:
     *     [0b10000000, 0b01000000]
     */
    private array $byteBuffer;

    /** @var int[] Used in {@link self::popCount} */
    private const bytePopCountLookupTable = [
        0, 1, 1, 2, 1, 2, 2, 3, 1, 2, 2, 3, 2, 3, 3, 4, 1, 2, 2, 3, 2, 3, 3, 4, 2, 3, 3, 4, 3, 4, 4, 5, 1, 2, 2, 3, 2, 3, 3, 4, 2, 3, 3, 4, 3, 4, 4, 5, 2, 3,
        3, 4, 3, 4, 4, 5, 3, 4, 4, 5, 4, 5, 5, 6, 1, 2, 2, 3, 2, 3, 3, 4, 2, 3, 3, 4, 3, 4, 4, 5, 2, 3, 3, 4, 3, 4, 4, 5, 3, 4, 4, 5, 4, 5, 5, 6, 2, 3, 3, 4,
        3, 4, 4, 5, 3, 4, 4, 5, 4, 5, 5, 6, 3, 4, 4, 5, 4, 5, 5, 6, 4, 5, 5, 6, 5, 6, 6, 7, 1, 2, 2, 3, 2, 3, 3, 4, 2, 3, 3, 4, 3, 4, 4, 5, 2, 3, 3, 4, 3, 4,
        4, 5, 3, 4, 4, 5, 4, 5, 5, 6, 2, 3, 3, 4, 3, 4, 4, 5, 3, 4, 4, 5, 4, 5, 5, 6, 3, 4, 4, 5, 4, 5, 5, 6, 4, 5, 5, 6, 5, 6, 6, 7, 2, 3, 3, 4, 3, 4, 4, 5,
        3, 4, 4, 5, 4, 5, 5, 6, 3, 4, 4, 5, 4, 5, 5, 6, 4, 5, 5, 6, 5, 6, 6, 7, 3, 4, 4, 5, 4, 5, 5, 6, 4, 5, 5, 6, 5, 6, 6, 7, 4, 5, 5, 6, 5, 6, 6, 7, 5, 6,
        6, 7, 6, 7, 7, 8,
    ];

    /** Creates a {@link PhpBitArray} with a backing buffer. */
    private function __construct(array $byteBuffer)
    {
        $numberOfBits = count($byteBuffer) * 8;
        parent::__construct($numberOfBits);
        $this->byteBuffer = $byteBuffer;
    }

    static function fromRawString(string $rawString): self
    {
        // See: https://stackoverflow.com/a/11466734
        $byteBuffer = unpack('C*', $rawString); // Caution: returns 1-based indexes
        $byteBuffer = array_values($byteBuffer); // Convert to 0-based indexes
        return new self($byteBuffer);
    }

    function toRawString(): string
    {
        return pack('C*', ...$this->byteBuffer);
    }

    static function create(int $numberOfBits): self
    {
        if ($numberOfBits <= 0 || ($numberOfBits % 8) !== 0)
            throw new InvalidArgumentException('$numberOfBits must be a multiple of 8 and greater than 0');

        $byteBuffer = array_fill(0, intdiv($numberOfBits, 8), 0);
        return new self($byteBuffer);
    }

    /**
     * Sets a value of the array.
     * @param $index int An integer `n` that is `0 <= n < this.length`.
     * @param $value bool The value to safe. Can be `true | false | 0 | 1`.
     * @returns self Reference to the same {@link BitArray}, so multiple calls can be chained.
     *
     * Time complexity: O(1)
     */
    function set(int $index, bool $value): self
    {
        if ($index < 0 || $this->numberOfBits <= $index)
            throw new OutOfBoundsException();

        $bitValue = (int)$value;

        $indexOfByteInBuffer = intdiv($index, 8);
        $indexOfBitInByte = 7 - ($index % 8); // "7 - " makes the MSB the bit with index 0 (instead of the LSB)

        $shiftedBit = 1 << $indexOfBitInByte;

        if ($bitValue) {
            $this->byteBuffer[$indexOfByteInBuffer] |= $shiftedBit;
        } else {
            $this->byteBuffer[$indexOfByteInBuffer] &= (~$shiftedBit) & 0xff;
        }

        return $this;
    }

    /**
     * Gets a value of the array.
     *
     * Time complexity: O(1)
     *
     * @param $index int An integer `n` that is `0 <= n < this.length`.
     * @returns bool value that indicates whether the bit was set.
     */
    function get(int $index): bool
    {
        if ($index < 0 || $this->numberOfBits <= $index)
            throw new OutOfBoundsException();

        $indexOfByteInBuffer = intdiv($index, 8);
        $indexOfBitInByte = 7 - ($index % 8); // "7 - " makes the MSB the bit with index 0 (instead of the LSB)

        $byte = $this->byteBuffer[$indexOfByteInBuffer];

        return ($byte & (1 << $indexOfBitInByte)) !== 0;
    }

    /**
     * Sets all `{@link bool}`s in the array to `false`. Keeps the array length.
     *
     * Time complexity: O(n) with n being the size of the array
     *
     * @returns self Reference to the same {@link BitArray}, so multiple calls can be chained.
     */
    function clear(): self
    {
        return $this->fill(false);
    }

    /**
     * Sets all `{@link bool}`s in the array to `value`. Keeps the array length.
     * @returns self Reference to the same {@link BitArray}, so multiple calls can be chained.
     */
    function fill(bool $value): self
    {
        $v = $value ? 255 : 0;
        $this->byteBuffer = array_fill(0, intdiv($this->numberOfBits, 8), $v);
        return $this;
    }

    /**
     * Counts the number of bits set to a specific {@link $needleValue}.
     *
     * Time complexity: O(n) with n being the size of the array
     * @returns int The number of bits set to a specific {@link $value}.
     */
    function popCount(bool $value = true): int
    {
        // TODO: Maybe use optimized implementation from https://stackoverflow.com/a/109025
        // Always counts the true bits and depending on what was asked for, subtract it from the length
        $ones = 0;
        foreach ($this->byteBuffer as $byte) {
            $ones += self::bytePopCountLookupTable[$byte & 0xff];
        }

        return $value
            ? $ones
            : ($this->numberOfBits - $ones);
    }

    function collectIndicesWithValue(bool $needleValue): array
    {
        $res = [];

        $byteCount = intdiv($this->numberOfBits, 8);
        $buffer = $this->byteBuffer;

        for ($byteIndex = 0; $byteIndex < $byteCount; ++$byteIndex) {
            $byte = $buffer[$byteIndex];

            $byteIndexPreMultiplied = $byteIndex * 8;
            for ($i = 0; $i < 8; ++$i) {
                $value = ($byte & (1 << (7 - $i))) !== 0;
                $index = $byteIndexPreMultiplied + $i;
                if ($value === $needleValue)
                    $res[] = $index;
            }
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
        $numberOfBytes = (int)($this->numberOfBits / 8);

        for ($i = 0; $i < $numberOfBytes; ++$i)
            $this->byteBuffer[$i] = (~$this->byteBuffer[$i]) & 0xff;
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
            throw new InvalidArgumentException('Both BitArrays must have the same length');

        if ($other instanceof PhpBitArray) {
            $numberOfBytes = (int)($this->numberOfBits / 8);

            for ($i = 0; $i < $numberOfBytes; ++$i)
                $this->byteBuffer[$i] &= $other->byteBuffer[$i];
            return;
        }

        parent::applyBitwiseAnd($other);
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
            throw new InvalidArgumentException('Both BitArrays must have the same length');

        if ($other instanceof PhpBitArray) {

            $numberOfBytes = (int)($this->numberOfBits / 8);

            for ($i = 0; $i < $numberOfBytes; ++$i)
                $this->byteBuffer[$i] |= $other->byteBuffer[$i];
            return;
        }

        parent::applyBitwiseOr($other);
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
            throw new InvalidArgumentException('Both BitArrays must have the same length');

        if ($other instanceof PhpBitArray) {

            $numberOfBytes = (int)($this->numberOfBits / 8);

            for ($i = 0; $i < $numberOfBytes; ++$i) {
                $v = $this->byteBuffer[$i];
                $this->byteBuffer[$i] = ($v ^ $other->byteBuffer[$i]) & 0xff;
            }
            return;
        }

        parent::applyBitwiseXor($other);
    }

    function clone(): PhpBitArray
    {
        // Passing the buffer will copy it (PHP internally uses COW semantics)
        return new PhpBitArray($this->byteBuffer);
    }

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
        $result = '';
        foreach ($this->byteBuffer as $byte)
            $result .= str_pad(decbin($byte), 8, '0', STR_PAD_LEFT);
        return $result;
    }

    function __serialize(): array
    {
        return [$this->toRawString()];
    }

    function __unserialize(array $data): void
    {
        $s = self::fromRawString($data[0]);
        $this->byteBuffer = $s->byteBuffer;
        $this->numberOfBits = $s->numberOfBits;
    }
}

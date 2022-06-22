<?php

namespace Nikeee\BitArray;

use InvalidArgumentException;
use OutOfBoundsException;

class PhpBitArray extends BitArray
{
    private array $byteBuffer;

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

    function get(int $index): bool
    {
        if ($index < 0 || $this->numberOfBits <= $index)
            throw new OutOfBoundsException();

        $indexOfByteInBuffer = intdiv($index, 8);
        $indexOfBitInByte = 7 - ($index % 8); // "7 - " makes the MSB the bit with index 0 (instead of the LSB)

        $byte = $this->byteBuffer[$indexOfByteInBuffer];

        return ($byte & (1 << $indexOfBitInByte)) !== 0;
    }

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

    function clear(): self
    {
        return $this->fill(false);
    }

    function fill(bool $value): self
    {
        $v = $value ? 255 : 0;
        $this->byteBuffer = array_fill(0, intdiv($this->numberOfBits, 8), $v);
        return $this;
    }

    function popCount(bool $value = true): int
    {
        // TODO: Maybe use optimized implementation from https://stackoverflow.com/a/109025
        // Always counts the true bits and depending on what was asked for, subtract it from the length
        $ones = 0;
        foreach ($this->byteBuffer as $byte) {
            if ($byte !== 0)
                $ones += self::numberOfSetBits($byte & 0xff);
        }

        return $value
            ? $ones
            : ($this->numberOfBits - $ones);
    }

    private static function numberOfSetBits(int $v): int
    {
        // See: https://stackoverflow.com/a/38391968
        $bitCount = $v - (($v >> 1) & 0x55555555);
        $bitCount = (($bitCount >> 2) & 0x33333333) + ($bitCount & 0x33333333);
        $bitCount = (($bitCount >> 4) + $bitCount) & 0x0F0F0F0F;
        $bitCount = (($bitCount >> 8) + $bitCount) & 0x00FF00FF;
        return (($bitCount >> 16) + $bitCount) & 0x0000FFFF;
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
     * Returns the bitwise negation of the array.
     *
     * Time complexity: O(n) with n being the size of the array
     */
    function applyBitwiseNot(): void
    {
        $numberOfBytes = (int)($this->numberOfBits / 8);
        for ($i = 0; $i < $numberOfBytes; ++$i)
            $this->byteBuffer[$i] = (~$this->byteBuffer[$i]) & 0xff;
    }

    function applyBitwiseAnd(BitArray $other): void
    {
        if ($this->numberOfBits !== $other->numberOfBits)
            throw new InvalidArgumentException('Both BitArrays must have the same length');

        if ($other instanceof PhpBitArray) {
            for ($i = 0; $i < $this->numberOfBits; ++$i)
                $this->byteBuffer[$i] &= $other->byteBuffer[$i];
        }
        parent::applyBitwiseAnd($other);
    }

    function applyBitwiseOr(BitArray $other): void
    {
        if ($this->numberOfBits !== $other->numberOfBits)
            throw new InvalidArgumentException('Both BitArrays must have the same length');

        if ($other instanceof PhpBitArray) {
            for ($i = 0; $i < $this->numberOfBits; ++$i)
                $this->byteBuffer[$i] |= $other->byteBuffer[$i];
        }
        parent::applyBitwiseOr($other);
    }

    function applyBitwiseXor(BitArray $other): void
    {
        if ($this->numberOfBits !== $other->numberOfBits)
            throw new InvalidArgumentException('Both BitArrays must have the same length');

        if ($other instanceof PhpBitArray) {
            for ($i = 0; $i < $this->numberOfBits; ++$i)
                $this->byteBuffer[$i] ^= $other->byteBuffer[$i];
        }
        parent::applyBitwiseXor($other);
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

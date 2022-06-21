<?php

namespace Nikeee\BitArray;

// TODO: Maybe subclass this with a version that uses GMP under the hood (or some other conditional magic)
class PhpBitArray extends BitArray
{
    private array $byteBuffer;
    /** @readonly */
    private int $numberOfBits;

    private function __construct(array $byteBuffer)
    {
        $this->byteBuffer = $byteBuffer;
        $this->numberOfBits = count($byteBuffer) * 8;
    }

    public static function fromRawString(string $rawString): PhpBitArray
    {
        // See: https://stackoverflow.com/a/11466734
        $byteBuffer = unpack('C*', $rawString); // Caution: returns 1-based indexes
        $byteBuffer = array_values($byteBuffer); // Convert to 0-based indexes
        return new self($byteBuffer);
    }

    public function toRawString(): string
    {
        return pack('C*', ...$this->byteBuffer);
    }

    public static function create(int $size): PhpBitArray
    {
        if ($size === 0 || ($size % 8) !== 0)
            throw new \InvalidArgumentException('$size must be a multiple of 8 and greater than 0');

        $byteBuffer = array_fill(0, $size, 0);
        return new self($byteBuffer);
    }

    public function set(int $index, bool $value): self
    {
        if (0 > $index || $index > $this->numberOfBits)
            throw new \OutOfBoundsException();

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

    public function get(int $index): bool
    {
        if (0 > $index || $index > $this->numberOfBits)
            throw new \OutOfBoundsException();

        $indexOfByteInBuffer = intdiv($index, 8);
        $indexOfBitInByte = 7 - ($index % 8); // "7 - " makes the MSB the bit with index 0 (instead of the LSB)

        $byte = $this->byteBuffer[$indexOfByteInBuffer];

        return ($byte & (1 << $indexOfBitInByte)) !== 0;
    }

    public function at(int $index): bool
    {
        $positiveIndex = $index < 0
            ? $this->numberOfBits + ($index % $this->numberOfBits)
            : $index;
        return $this->get($positiveIndex);
    }

    public function clear(): self
    {
        return $this->fill(false);
    }

    public function fill(bool $value): self
    {
        $v = $value ? 255 : 0;
        $this->byteBuffer = array_fill(0, $this->numberOfBits, $v);
        return $this;
    }

    public function popCount(bool $value = true): int
    {
        // gmp_popcount is actually _way_ faster than this
        // 10 rounds yield this:
        //      gmp_popcount: 0.00126
        //      this method: 3.559581
        // (on a bit array of size 8000000)
        // However, we may not have GMP available and the internal representation is incompatible with our BitArray
        //(at the moment)

        // packing the array and passing it to GMP is actually faster than the pure PHP implementation:
        //                       gmp_popcount: 0.001204
        // pack() gmp_import and gmp_popcount: 0.080502
        // For popcount, the memory layout doesn't matter, so we can fall back to GMP if it is available

        if (extension_loaded('gmp')) {
            $packedBuffer = $this->toRawString();
            $number = gmp_import($packedBuffer);
            $ones = gmp_popcount($number);
            return $value
                ? $ones
                : ($this->numberOfBits - $ones);
        }

        // TODO: Maybe use optimized implementation from https://stackoverflow.com/a/109025
        // Always counts the true bits and depending on what was asked for, subtract it from the length
        $ones = 0;
        foreach ($this->byteBuffer as $byte) {
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

    /*
    public function iterate()
    {
        $byteCount = intdiv($this->size, 8);
        $buffer = $this->byteBuffer;

        for ($byteIndex = 0; $byteIndex < $byteCount; ++$byteIndex) {
            $byte = $buffer[$byteIndex];

            $byteIndexPreMultiplied = $byteIndex * 8;
            for ($i = 0; $i < 8; ++$i) {
                $value = ($byte & (1 << (7 - $i))) !== 0;
                $index = $byteIndexPreMultiplied + $i;
                if ($value)
                    yield $index;
                // yield [$index, $value];
            }
        }
    }
    */

    public function getIndicesWithValue(bool $needleValue): array
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
    public function negated(): PhpBitArray
    {
        $resultBuffer = $this->byteBuffer; // PHP copies arrays by default, so this is a copy
        for ($i = 0; $i < $this->numberOfBits; ++$i)
            $resultBuffer[$i] |= (~$resultBuffer[$i]) & 0xff;

        return new self($resultBuffer);
    }

    public static function bitwiseAnd(PhpBitArray $a, PhpBitArray $b): PhpBitArray
    {
        if ($a->numberOfBits !== $b->numberOfBits)
            throw new \InvalidArgumentException('Both BitArrays must have the same length');

        $resultBuffer = $a->byteBuffer; // PHP copies arrays by default, so this is a copy
        for ($i = 0; $i < $a->numberOfBits; ++$i)
            $resultBuffer[$i] &= $b->byteBuffer[$i];

        return new self($resultBuffer);
    }

    public static function bitwiseOr(PhpBitArray $a, PhpBitArray $b): PhpBitArray
    {
        if ($a->numberOfBits !== $b->numberOfBits)
            throw new \InvalidArgumentException('Both BitArrays must have the same length');

        $resultBuffer = $a->byteBuffer; // PHP copies arrays by default, so this is a copy
        for ($i = 0; $i < $a->numberOfBits; ++$i)
            $resultBuffer[$i] |= $b->byteBuffer[$i];

        return new self($resultBuffer);
    }

    public static function bitwiseXor(PhpBitArray $a, PhpBitArray $b): PhpBitArray
    {
        if ($a->numberOfBits !== $b->numberOfBits)
            throw new \InvalidArgumentException('Both BitArrays must have the same length');

        $resultBuffer = $a->byteBuffer; // PHP copies arrays by default, so this is a copy
        for ($i = 0; $i < $a->numberOfBits; ++$i)
            $resultBuffer[$i] ^= $b->byteBuffer[$i];

        return new self($resultBuffer);
    }

    public function __unserialize(array $data): void
    {
        $s = self::fromRawString($data[0]);
        $this->byteBuffer = $s->byteBuffer;
        $this->numberOfBits = $s->numberOfBits;
    }
}

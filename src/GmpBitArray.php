<?php
/** @noinspection PhpComposerExtensionStubsInspection */

namespace Nikeee\BitArray;

use GMP;
use InvalidArgumentException;
use OutOfBoundsException;

class GmpBitArray extends BitArray
{
    private GMP $n;

    private function __construct(GMP $n, int $numberOfBits)
    {
        parent::__construct($numberOfBits);
        $this->n = $n;
        $this->numberOfBits = $numberOfBits;
    }

    static function fromRawString(string $rawString): self
    {
        // For some reason, strlen can be used to get the number of bytes in a binary string:
        // https://stackoverflow.com/a/53592972
        $byteCount = strlen($rawString);

        $n = gmp_import($rawString);
        if ($n === false)
            throw new InvalidArgumentException('Could not parse $rawString');
        return new self($n, $byteCount * 8);
    }

    function toRawString(): string
    {
        return gmp_export($this->n);
    }

    function get(int $index): bool
    {
        if (0 > $index || $index > $this->numberOfBits)
            throw new OutOfBoundsException();

        // Use `$this->numberOfBits - $index` as index because GMP layouts the data in reversed order
        return gmp_testbit($this->n, $this->numberOfBits - $index);
    }

    function set(int $index, bool $value): self
    {
        if (0 > $index || $index > $this->numberOfBits)
            throw new OutOfBoundsException();

        // Use `$this->numberOfBits - $index` as index because GMP layouts the data in reversed order
        gmp_setbit($this->n, $this->numberOfBits - $index, $value);
        return $this;
    }

    function at(int $index): bool
    {
        $positiveIndex = $index < 0
            ? $this->numberOfBits + ($index % $this->numberOfBits)
            : $index;
        return $this->get($positiveIndex);
    }

    function clear(): self
    {
        return $this->fill(false);
    }

    function fill(bool $value): self
    {
        $this->n = $value
            ? self::buildOnes($this->numberOfBits)
            : gmp_init('0');
        return $this;
    }

    function popCount(bool $value = true): int
    {
        $ones = gmp_popcount($this->n);
        return $value
            ? $ones
            : $this->numberOfBits - $ones;
    }

    function collectIndicesWithValue(bool $needleValue): array
    {
        $n = $this->n;
        $lastIndex = 0;
        $indexes = [];
        if ($needleValue) {
            while (($lastIndex = gmp_scan1($n, $lastIndex)) !== -1) {
                $indexes[] = $lastIndex;
                ++$lastIndex;
            }
        } else {
            while (($lastIndex = gmp_scan0($n, $lastIndex)) !== -1) {
                $indexes[] = $lastIndex;
                ++$lastIndex;
            }
        }
        return $indexes;
    }

    function applyBitwiseNot(): void
    {
        $ones = self::buildOnes($this->numberOfBits);
        $this->n = gmp_xor($this->n, $ones);
    }

    private static function buildOnes(int $numberOfBits): GMP
    {
        // We're ensured that $numberOfBits is always divisible by 8
        // We can construct a new GMP number that parses (n/8) * '0xFF'
        $binaryString = str_repeat("\xff", (int)($numberOfBits / 8));
        return gmp_import($binaryString);
    }

    function applyBitwiseAnd(BitArray $other): void
    {
        if ($this->numberOfBits !== $other->numberOfBits)
            throw new InvalidArgumentException('Both BitArrays must have the same length');

        if ($other instanceof GmpBitArray) {
            $this->n = gmp_and($this->n, $other->n);
            return;
        }
        parent::applyBitwiseAnd($other);
    }

    function applyBitwiseOr(BitArray $other): void
    {
        if ($this->numberOfBits !== $other->numberOfBits)
            throw new InvalidArgumentException('Both BitArrays must have the same length');

        if ($other instanceof GmpBitArray) {
            $this->n = gmp_or($this->n, $other->n);
            return;
        }
        parent::applyBitwiseOr($other);
    }

    function applyBitwiseXor(BitArray $other): void
    {
        if ($this->numberOfBits !== $other->numberOfBits)
            throw new InvalidArgumentException('Both BitArrays must have the same length');

        if ($other instanceof GmpBitArray) {
            $this->n = gmp_xor($this->n, $other->n);
            return;
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
        $this->n = $s->n;
        $this->numberOfBits = $s->numberOfBits;
    }
}

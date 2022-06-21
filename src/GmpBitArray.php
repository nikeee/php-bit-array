<?php
/** @noinspection PhpComposerExtensionStubsInspection */

namespace Nikeee\BitArray;

use GMP;

class GmpBitArray extends BitArray
{
    private GMP $n;
    /** @readonly */
    private int $numberOfBits;

    private function __construct(GMP $n, int $numberOfBits)
    {
        $this->n = $n;
        $this->numberOfBits = $numberOfBits;
    }

    public static function fromRawString(string $rawString): self
    {
        // For some reason, strlen can be used to get the number of bytes in a binary string:
        // https://stackoverflow.com/a/53592972
        $byteCount = strlen($rawString);

        $n = gmp_import($rawString);
        if ($n === false)
            throw new \InvalidArgumentException('Could not parse $rawString');
        return new self($n, $byteCount * 8);
    }

    function toRawString(): string
    {
        return gmp_export($this->n);
    }

    function get(int $index): bool
    {
        // Use `$this->numberOfBits - $index` as index because GMP layouts the data in reversed order
        return gmp_testbit($this->n, $this->numberOfBits - $index);
    }

    function set(int $index, bool $value): self
    {
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
        $this->n = gmp_init('0');
    }

    function fill(bool $value): self
    {
        if ($value) {
            // TODO: Benchmark this:
            /*
            // Maybe this is not the fastest solution, but it works
            $n = $this->n;
            for ($i = 0; $i < $this->numberOfBits; ++$i)
                gmp_setbit($n, $i, true);
            return $this;
            */

            // We're ensured that $numberOfBits is always divisible by 8
            // We can construct a new GMP number that parses (n/8) * '0xFF'
            $binaryString = str_repeat("\xff", (int)($this->numberOfBits / 8));
            $this->n = gmp_import($binaryString);
            return $this;
        }
        return $this->clear();
    }

    function popCount(bool $value = true): int
    {
        $ones = gmp_popcount($this->n);
        return $value
            ? $ones
            : $this->numberOfBits - $ones;
    }

    public function __serialize(): array
    {
        return [$this->toRawString()];
    }

    public function __unserialize(array $data): void
    {
        $s = self::fromRawString($data[0]);
        $this->n = $s->n;
        $this->numberOfBits = $s->numberOfBits;
    }
}

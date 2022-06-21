<?php

namespace Nikeee\BitArray;

class BitArrayFactory
{
    public static function fromRawString(string $rawString): BitArray
    {
        if (extension_loaded('gmp')) {
            return GmpBitArray::fromRawString($rawString);
        }
        return PhpBitArray::fromRawString($rawString);
    }
}

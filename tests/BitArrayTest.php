<?php declare(strict_types=1);

namespace Nikeee\BitArray;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use TRegx\DataProvider\DataProviders;

final class BitArrayTest extends TestCase
{
    /** @dataProvider provideBitArrayWithValidBitArraySizes */
    function testCreate($bitArrayClass, int $bitArraySize)
    {
        $arr = $bitArrayClass::create($bitArraySize);
        $this->assertEquals($bitArraySize, $arr->getNumberOfBits());
    }

    /** @dataProvider provideBitArrayWithInvalidBitArraySizes */
    function testCreateWithInvalidSize($bitArrayClass, int $bitArraySize)
    {
        $this->expectException(InvalidArgumentException::class);
        $arr = $bitArrayClass::create($bitArraySize);
        $this->assertEquals($bitArraySize, $arr->getNumberOfBits());
    }

    /** @dataProvider provideBitArrayWithValidBitArraySizes */
    function testFill($bitArrayClass, int $bitArraySize)
    {
        $arr = $bitArrayClass::create($bitArraySize);

        $arr->fill(true);
        $this->assertEquals($bitArraySize, $arr->getNumberOfBits());
        $this->assertEquals($arr->getNumberOfBits(), $arr->popCount(true));
        $this->assertEquals(0, $arr->popCount(false));

        $arr->fill(false);
        $this->assertEquals($bitArraySize, $arr->getNumberOfBits());
        $this->assertEquals($arr->getNumberOfBits(), $arr->popCount(false));
        $this->assertEquals(0, $arr->popCount(true));
    }

    /** @dataProvider provideBitArrayWithValidBitArraySizes */
    function testClear($bitArrayClass, int $bitArraySize)
    {
        $arr = $bitArrayClass::create($bitArraySize);

        $arr->set(0, true);
        $arr->set(1, true);

        $arr->clear();
        $this->assertEquals($bitArraySize, $arr->getNumberOfBits());
        $this->assertEquals($arr->getNumberOfBits(), $arr->popCount(false));
        $this->assertEquals(0, $arr->popCount(true));
    }

    /** @dataProvider provideBitArrayWithValidBitArraySizes */
    function testPopCount($bitArrayClass, int $bitArraySize)
    {
        $arr = $bitArrayClass::create($bitArraySize);

        $arr->set(0, true);
        $arr->set(1, true);

        $this->assertEquals($arr->getNumberOfBits() - 2, $arr->popCount(false));
        $this->assertEquals(2, $arr->popCount(true));
    }


    // #region Providers

    function provideBitArrayImplementation(): array
    {
        return [
            [BitArray::class],
            [GmpBitArray::class],
            [PhpBitArray::class],
        ];
    }

    function provideInvalidBitArraySizes(): array
    {
        return [
            [-8], [-1], [0], [1], [2], [4], [7], [9], [10], [11],
        ];
    }

    function provideValidBitArraySizes(): array
    {
        return [
            [8], [16], [32], [24], [48], [40],
        ];
    }

    function provideBitArrayWithInvalidBitArraySizes(): array
    {
        return DataProviders::cross(
            $this->provideBitArrayImplementation(),
            $this->provideInvalidBitArraySizes(),
        );
    }

    function provideBitArrayWithValidBitArraySizes(): array
    {
        return DataProviders::cross(
            $this->provideBitArrayImplementation(),
            $this->provideValidBitArraySizes(),
        );
    }

    // #endregion
}

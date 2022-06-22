<?php declare(strict_types=1);

namespace Nikeee\BitArray;

use InvalidArgumentException;
use OutOfBoundsException;
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

    /** @dataProvider provideBitArrayImplementation */
    function testSimpleGetAndSet($bitArrayClass)
    {
        $arr = $bitArrayClass::create(24)
            ->set(0, true)
            ->set(1, true)
            ->set(5, true)
            ->set(7, true)
            ->set(10, true)
            ->set(19, true);

        $this->assertEquals(true, $arr->get(0));
        $this->assertEquals(true, $arr->get(1));
        $this->assertEquals(true, $arr->get(5));
        $this->assertEquals(true, $arr->get(7));
        $this->assertEquals(true, $arr->get(10));
        $this->assertEquals(true, $arr->get(19));
        $this->assertEquals(false, $arr->get(23));

        $this->assertEquals(24, $arr->getNumberOfBits());

        $arr->clear();
        $this->assertEquals(24, $arr->getNumberOfBits());

        $this->assertEquals(false, $arr->get(0));
        $this->assertEquals(false, $arr->get(1));
        $this->assertEquals(false, $arr->get(5));
        $this->assertEquals(false, $arr->get(7));
        $this->assertEquals(false, $arr->get(10));
        $this->assertEquals(false, $arr->get(19));
        $this->assertEquals(false, $arr->get(23));
    }

    /** @dataProvider provideBitArrayImplementation */
    function testAt($bitArrayClass)
    {
        $arr = $bitArrayClass::create(8);

        $arr->set(0, true);
        $this->assertEquals(true, $arr->at(0));

        $arr->set(1, true);
        $this->assertEquals(true, $arr->at(0));
        $this->assertEquals(true, $arr->at(1));

        $arr->set(7, true);
        $this->assertEquals(true, $arr->at(0));
        $this->assertEquals(true, $arr->at(1));
        $this->assertEquals(true, $arr->at(7));
        $this->assertEquals(true, $arr->at(-1));
    }

    /** @dataProvider provideBitArrayImplementation */
    function testAtNoWrapAround($bitArrayClass)
    {
        // JS's Array.at does not handle wrap-around (`[0].at(-10)`), so we also don't do it
        $this->expectException(OutOfBoundsException::class);

        $arr = $bitArrayClass::create(8);
        $arr->at(-10);
    }

    /** @dataProvider provideBitArrayImplementation */
    function testSetOutOfBounds0($bitArrayClass)
    {
        $this->expectException(OutOfBoundsException::class);
        $bitArrayClass::create(8)->set(8, true);
    }

    /** @dataProvider provideBitArrayImplementation */
    function testSetOutOfBounds1($bitArrayClass)
    {
        $this->expectException(OutOfBoundsException::class);
        $bitArrayClass::create(8)->set(-1, true);
    }

    /** @dataProvider provideBitArrayImplementation */
    function testGetOutOfBounds0($bitArrayClass)
    {
        $this->expectException(OutOfBoundsException::class);
        $bitArrayClass::create(8)->get(8);
    }

    /** @dataProvider provideBitArrayImplementation */
    function testGetOutOfBounds1($bitArrayClass)
    {
        $this->expectException(OutOfBoundsException::class);
        $bitArrayClass::create(8)->get(-1);
    }

    /** @dataProvider provideBitArrayImplementation */
    function testGetOutOfBounds2($bitArrayClass)
    {
        $this->expectException(OutOfBoundsException::class);
        $bitArrayClass::create(8)->get(16);
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

    function provideTwoBitArrayImplementations(): array {
        return DataProviders::cross(
            $this->provideBitArrayImplementation(),
            $this->provideBitArrayImplementation(),
        );
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

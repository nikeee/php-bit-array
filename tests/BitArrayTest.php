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

    /** @dataProvider provideBitArrayImplementation */
    function testToRawString($bitArrayClass)
    {
        $arr = $bitArrayClass::create(8)
            ->set(0, true)
            ->set(1, true)
            ->set(5, true)
            ->set(7, true);

        echo bin2hex($arr->toRawString()) . "\n";

        $this->assertEquals(1, strlen($arr->toRawString()));
        $this->assertEquals("\xC5", $arr->toRawString());

        $arr = $bitArrayClass::create(16)
            ->set(0, true)
            ->set(1, true)
            ->set(5, true)
            ->set(7, true);

        $this->assertEquals(2, strlen($arr->toRawString()));
        $this->assertEquals("\xC5\x00", $arr->toRawString());

        $arr->set(15, true);
        $this->assertEquals(2, strlen($arr->toRawString()));
        $this->assertEquals("\xC5\x01", $arr->toRawString());
    }

    /** @dataProvider provideTwoBitArrayImplementationsWithSize */
    function testFromRawStringConsistency($firstClass, $secondClass, $arraySize)
    {
        $arr0 = $firstClass::create($arraySize)
            ->set(0, true)
            ->set(1, true)
            ->set(5, true)
            ->set(7, true);

        $arr1 = $secondClass::fromRawString($arr0->toRawString());

        $this->assertEquals($arraySize, $arr0->getNumberOfBits());
        $this->assertEquals($arraySize, $arr1->getNumberOfBits());
        $this->assertEquals($arr0->getNumberOfBits(), $arr1->getNumberOfBits());

        $this->assertCount(4, $arr0->collectIndicesWithValue(true));
        $this->assertCount(4, $arr1->collectIndicesWithValue(true));
        $this->assertCount($arraySize - 4, $arr0->collectIndicesWithValue(false));
        $this->assertCount($arraySize - 4, $arr1->collectIndicesWithValue(false));

        $this->assertSame(
            $arr0->collectIndicesWithValue(false),
            $arr1->collectIndicesWithValue(false),
        );

        $this->assertSame(
            $arr0->collectIndicesWithValue(true),
            $arr1->collectIndicesWithValue(true),
        );
    }

    /** @dataProvider provideBitArrayWithValidBitArraySizes */
    function testApplyBitwiseNot($bitArrayClass, $arraySize)
    {
        $arr0 = $bitArrayClass::create($arraySize);
        $arr0->applyBitwiseNot();
        $this->assertEquals($arr0->getNumberOfBits(), $arr0->popCount(true));
        $this->assertEquals(0, $arr0->popCount(false));

        $arr0->applyBitwiseNot();
        $this->assertEquals(0, $arr0->popCount(true));
        $this->assertEquals($arr0->getNumberOfBits(), $arr0->popCount(false));

        $arr0 = $bitArrayClass::create($arraySize)
            ->set(0, true)
            ->set(1, true)
            ->set(5, true)
            ->set(7, true);

        $this->assertEquals(4, $arr0->popCount(true));
        $this->assertEquals(true, $arr0->get(0));
        $this->assertEquals(true, $arr0->get(1));
        $this->assertEquals(true, $arr0->get(5));
        $this->assertEquals(true, $arr0->get(7));
        $this->assertEquals(false, $arr0->get(2));

        $arr0->applyBitwiseNot();
        $this->assertEquals(4, $arr0->popCount(false));
        $this->assertEquals(false, $arr0->get(0));
        $this->assertEquals(false, $arr0->get(1));
        $this->assertEquals(false, $arr0->get(5));
        $this->assertEquals(false, $arr0->get(7));
        $this->assertEquals(true, $arr0->get(2));
    }

    /** @dataProvider provideTwoBitArrayImplementations */
    function testApplyBitwiseAndWithUnequalSizes($firstClass, $secondClass)
    {
        $arr0 = $firstClass::create(8);
        $arr1 = $secondClass::create(16);
        $this->expectException(InvalidArgumentException::class);
        $arr0->applyBitwiseAnd($arr1);
    }

    /** @dataProvider provideTwoBitArrayImplementationsWithSize */
    function testApplyBitwiseAnd($firstClass, $secondClass, $arraySize)
    {
        $arr0 = $firstClass::create($arraySize);
        $arr1 = $secondClass::create($arraySize);

        $arr0->clear()->set(1, false);
        $arr1->clear()->set(1, false);
        $this->assertEquals(0, $arr0->popCount(true));
        $this->assertEquals(0, $arr1->popCount(true));

        $arr0->applyBitwiseAnd($arr1);

        $this->assertEquals(false, $arr0->get(1));
        $this->assertEquals(false, $arr1->get(1));
        $this->assertEquals(0, $arr0->popCount(true));
        $this->assertEquals(0, $arr1->popCount(true));


        $arr0->clear()->set(1, true);
        $arr1->clear()->set(1, false);
        $this->assertEquals(1, $arr0->popCount(true));
        $this->assertEquals(0, $arr1->popCount(true));

        $arr0->applyBitwiseAnd($arr1);

        $this->assertEquals(false, $arr0->get(1));
        $this->assertEquals(false, $arr1->get(1));
        $this->assertEquals(0, $arr0->popCount(true));
        $this->assertEquals(0, $arr1->popCount(true));


        $arr0->clear()->set(1, true);
        $arr1->clear()->set(1, true);
        $this->assertEquals(1, $arr0->popCount(true));
        $this->assertEquals(1, $arr1->popCount(true));

        $arr0->applyBitwiseAnd($arr1);

        $this->assertEquals(true, $arr0->get(1));
        $this->assertEquals(true, $arr1->get(1));
        $this->assertEquals(1, $arr0->popCount(true));
        $this->assertEquals(1, $arr1->popCount(true));


        $arr0->clear()->set(1, false);
        $arr1->clear()->set(1, true);
        $this->assertEquals(0, $arr0->popCount(true));
        $this->assertEquals(1, $arr1->popCount(true));

        $arr0->applyBitwiseAnd($arr1);

        $this->assertEquals(false, $arr0->get(1));
        $this->assertEquals(true, $arr1->get(1));
        $this->assertEquals(0, $arr0->popCount(true));
        $this->assertEquals(1, $arr1->popCount(true));
    }

    /** @dataProvider provideTwoBitArrayImplementations */
    function testApplyBitwiseOrWithUnequalSizes($firstClass, $secondClass)
    {
        $arr0 = $firstClass::create(8);
        $arr1 = $secondClass::create(16);
        $this->expectException(InvalidArgumentException::class);
        $arr0->applyBitwiseOr($arr1);
    }

    /** @dataProvider provideTwoBitArrayImplementationsWithSize */
    function testApplyBitwiseOr($firstClass, $secondClass, $arraySize)
    {
        $arr0 = $firstClass::create($arraySize);
        $arr1 = $secondClass::create($arraySize);

        $arr0->clear()->set(1, false);
        $arr1->clear()->set(1, false);
        $this->assertEquals(0, $arr0->popCount(true));
        $this->assertEquals(0, $arr1->popCount(true));

        $arr0->applyBitwiseOr($arr1);

        $this->assertEquals(false, $arr0->get(1));
        $this->assertEquals(false, $arr1->get(1));
        $this->assertEquals(0, $arr0->popCount(true));
        $this->assertEquals(0, $arr1->popCount(true));


        $arr0->clear()->set(1, true);
        $arr1->clear()->set(1, false);
        $this->assertEquals(1, $arr0->popCount(true));
        $this->assertEquals(0, $arr1->popCount(true));

        $arr0->applyBitwiseOr($arr1);

        $this->assertEquals(true, $arr0->get(1));
        $this->assertEquals(false, $arr1->get(1));
        $this->assertEquals(1, $arr0->popCount(true));
        $this->assertEquals(0, $arr1->popCount(true));


        $arr0->clear()->set(1, true);
        $arr1->clear()->set(1, true);
        $this->assertEquals(1, $arr0->popCount(true));
        $this->assertEquals(1, $arr1->popCount(true));

        $arr0->applyBitwiseOr($arr1);

        $this->assertEquals(true, $arr0->get(1));
        $this->assertEquals(true, $arr1->get(1));
        $this->assertEquals(1, $arr0->popCount(true));
        $this->assertEquals(1, $arr1->popCount(true));


        $arr0->clear()->set(1, false);
        $arr1->clear()->set(1, true);
        $this->assertEquals(0, $arr0->popCount(true));
        $this->assertEquals(1, $arr1->popCount(true));

        $arr0->applyBitwiseOr($arr1);

        $this->assertEquals(true, $arr0->get(1));
        $this->assertEquals(true, $arr1->get(1));
        $this->assertEquals(1, $arr0->popCount(true));
        $this->assertEquals(1, $arr1->popCount(true));
    }

    /** @dataProvider provideTwoBitArrayImplementations */
    function testApplyBitwiseXorWithUnequalSizes($firstClass, $secondClass)
    {
        $arr0 = $firstClass::create(8);
        $arr1 = $secondClass::create(16);
        $this->expectException(InvalidArgumentException::class);
        $arr0->applyBitwiseXor($arr1);
    }

    /** @dataProvider provideTwoBitArrayImplementationsWithSize */
    function testApplyBitwiseXor($firstClass, $secondClass, $arraySize)
    {
        $arr0 = $firstClass::create($arraySize);
        $arr1 = $secondClass::create($arraySize);

        $arr0->clear()->set(1, false);
        $arr1->clear()->set(1, false);
        $this->assertEquals(0, $arr0->popCount(true));
        $this->assertEquals(0, $arr1->popCount(true));

        $arr0->applyBitwiseXor($arr1);

        $this->assertEquals(false, $arr0->get(1));
        $this->assertEquals(false, $arr1->get(1));
        $this->assertEquals(0, $arr0->popCount(true));
        $this->assertEquals(0, $arr1->popCount(true));


        $arr0->clear()->set(1, true);
        $arr1->clear()->set(1, false);
        $this->assertEquals(1, $arr0->popCount(true));
        $this->assertEquals(0, $arr1->popCount(true));

        $arr0->applyBitwiseXor($arr1);

        $this->assertEquals(true, $arr0->get(1));
        $this->assertEquals(false, $arr1->get(1));
        $this->assertEquals(1, $arr0->popCount(true));
        $this->assertEquals(0, $arr1->popCount(true));


        $arr0->clear()->set(1, true);
        $arr1->clear()->set(1, true);
        $this->assertEquals(1, $arr0->popCount(true));
        $this->assertEquals(1, $arr1->popCount(true));

        $arr0->applyBitwiseXor($arr1);

        $this->assertEquals(false, $arr0->get(1));
        $this->assertEquals(true, $arr1->get(1));
        $this->assertEquals(0, $arr0->popCount(true));
        $this->assertEquals(1, $arr1->popCount(true));


        $arr0->clear()->set(1, false);
        $arr1->clear()->set(1, true);
        $this->assertEquals(0, $arr0->popCount(true));
        $this->assertEquals(1, $arr1->popCount(true));

        $arr0->applyBitwiseXor($arr1);

        $this->assertEquals(true, $arr0->get(1));
        $this->assertEquals(true, $arr1->get(1));
        $this->assertEquals(1, $arr0->popCount(true));
        $this->assertEquals(1, $arr1->popCount(true));
    }

    /** @dataProvider provideBitArrayImplementation */
    function testToBitString($bitArrayClass)
    {
        $a = $bitArrayClass::create(8);
        $this->assertEquals('00000000', $a->toBitString());

        $a->set(0, true);
        $this->assertEquals('10000000', $a->toBitString());

        $a->set(1, true);
        $this->assertEquals('11000000', $a->toBitString());

        $a->set(7, true);
        $this->assertEquals('11000001', $a->toBitString());


        $a = $bitArrayClass::create(16);
        $this->assertEquals('0000000000000000', $a->toBitString());

        $a->set(0, true);
        $this->assertEquals('1000000000000000', $a->toBitString());

        $a->set(1, true);
        $this->assertEquals('1100000000000000', $a->toBitString());

        $a->set(7, true);
        $this->assertEquals('1100000100000000', $a->toBitString());

        $a->set(14, true);
        $this->assertEquals('1100000100000010', $a->toBitString());


        $a = $bitArrayClass::create(24);
        $this->assertEquals('000000000000000000000000', $a->toBitString());

        $a->set(0, true);
        $this->assertEquals('100000000000000000000000', $a->toBitString());

        $a->set(1, true);
        $this->assertEquals('110000000000000000000000', $a->toBitString());

        $a->set(7, true);
        $this->assertEquals('110000010000000000000000', $a->toBitString());

        $a->set(14, true);
        $this->assertEquals('110000010000001000000000', $a->toBitString());

        $a->set(22, true);
        $this->assertEquals('110000010000001000000010', $a->toBitString());

        $a->set(23, true);
        $this->assertEquals('110000010000001000000011', $a->toBitString());

        $a->set(0, false);
        $this->assertEquals('010000010000001000000011', $a->toBitString());
    }

    /** @dataProvider provideBitArrayWithValidBitArraySizes */
    function testClone($bitArrayClass, $arraySize)
    {
        $arr0 = $bitArrayClass::create($arraySize)
            ->set(0, true)
            ->set(1, true)
            ->set(7, true);

        $arr1 = $arr0->clone();

        $arr0->set(5, true);
        $this->assertEquals(true, $arr0->get(5));
        $this->assertEquals(false, $arr1->get(5));

        $arr1->set(6, true);
        $this->assertEquals(true, $arr0->get(5));
        $this->assertEquals(false, $arr1->get(5));
        $this->assertEquals(false, $arr0->get(6));
        $this->assertEquals(true, $arr1->get(6));

        $arr1->set(6, false);
        $this->assertEquals(true, $arr0->get(5));
        $this->assertEquals(false, $arr1->get(5));
        $this->assertEquals(false, $arr0->get(6));
        $this->assertEquals(false, $arr1->get(6));
    }

    /** @dataProvider provideBitArrayWithValidBitArraySizes */
    function testCloneAndEnlarge($bitArrayClass, $arraySize)
    {
        $arr0 = $bitArrayClass::create($arraySize)
            ->set(0, true)
            ->set(1, true)
            ->set(7, true);

        $arr1 = $arr0->cloneAndEnlarge($arraySize + 8);

        $this->assertEquals($arraySize, $arr0->getNumberOfBits());
        $this->assertEquals($arraySize + 8, $arr1->getNumberOfBits());
        $this->assertEquals(3, $arr0->popCount(true));
        $this->assertEquals(3, $arr1->popCount(true));

        $arr0->set(5, true);
        $this->assertEquals(true, $arr0->get(5));
        $this->assertEquals(false, $arr1->get(5));

        $arr1->set(6, true);
        $this->assertEquals(true, $arr0->get(5));
        $this->assertEquals(false, $arr1->get(5));
        $this->assertEquals(false, $arr0->get(6));
        $this->assertEquals(true, $arr1->get(6));

        $arr1->set(6, false);
        $this->assertEquals(true, $arr0->get(5));
        $this->assertEquals(false, $arr1->get(5));
        $this->assertEquals(false, $arr0->get(6));
        $this->assertEquals(false, $arr1->get(6));
    }

    // #region Providers

    function provideBitArrayImplementation(): array
    {
        return [
            [BitArray::class],
            [PhpBitArray::class],
            // [GmpBitArray::class],
        ];
    }

    function provideTwoBitArrayImplementations(): array
    {
        return DataProviders::cross(
            $this->provideBitArrayImplementation(),
            $this->provideBitArrayImplementation(),
        );
    }

    function provideTwoBitArrayImplementationsWithSize(): array
    {
        return DataProviders::cross(
            $this->provideBitArrayImplementation(),
            $this->provideBitArrayImplementation(),
            $this->provideValidBitArraySizes(),
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

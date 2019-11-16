<?php
namespace mx1700;
use PHPUnit\Framework\TestCase;
include '../vendor/autoload.php';

class SequenceTest extends TestCase
{
    function testInitArray()
    {
        $list = [1, 2, 3, 4];
        $s = Sequence::of($list);
        $this->assertEquals($list, $s->toArray());
    }

    function testInitGar()
    {
        $g = function () {
            for ($i = 0; $i < 10; $i++) {
                yield $i;
            }
        };
        $arr = iterator_to_array($g());
        $seq = Sequence::of($g());

        $this->assertEquals($arr, $seq->toArray());
    }

    function testMap()
    {
        $this->assertEquals(
            [2, 4, 6, 8],
            Sequence::of([1, 2, 3, 4])->map(function ($a) {
                return $a * 2;
            })->toArray()
        );
    }

    function testFilter()
    {
        $this->assertEquals(
            [3 => 4, 4 => 5],
            Sequence::of([1, 2, 3, 4, 5])->filter(function ($a) {
                return $a > 3;
            })->toArray()
        );
    }

    function testSkip()
    {
        $this->assertEquals(
            [2 => 3, 3 => 4, 4 => 5],
            Sequence::of([1, 2, 3, 4, 5])->skip(2)->toArray()
        );
    }

    function testLimit()
    {
        $this->assertEquals(
            [1, 2, 3],
            Sequence::of([1, 2, 3, 4, 5])->limit(3)->toArray()
        );
    }

    function testFirst()
    {
        $this->assertEquals(
            1,
            Sequence::of([1, 2, 3, 4, 5])->first()
        );
    }

    function testFirstFilter()
    {
        $this->assertEquals(
            2,
            Sequence::of([1, 2, 3, 4, 5])->first(function ($a) {
                return $a > 1;
            })
        );
    }

    function testLast()
    {
        $this->assertEquals(
            5,
            Sequence::of([1, 2, 3, 4, 5])->last()
        );
    }

    function testLastFilter()
    {
        $this->assertEquals(
            4,
            Sequence::of([1, 2, 3, 4, 5])->last(function ($a) {
                return $a < 5;
            })
        );
    }

    function testReduce()
    {
        $this->assertEquals(
            6,
            Sequence::of([1, 2, 3])->reduce(function ($r, $c) {
                return $r + $c;
            })
        );
    }

    function testReduceHasInit()
    {
        $this->assertEquals(
            7,
            Sequence::of([1, 2, 3])->reduce(function ($r, $c) {
                return $r + $c;
            }, 1)
        );
    }

    function testAll()
    {
        $this->assertEquals(
            true,
            Sequence::of([1, 2, 3])->all(function($a) { return $a < 5; })
        );
    }

    function testAllFalse()
    {
        $this->assertEquals(
            false,
            Sequence::of([1, 2, 3])->all(function($a) { return $a < 3; })
        );
    }
}
<?php
namespace mx1700;
use Exception;
use OutOfRangeException;
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

    function testFilterNotEmpty()
    {
        $this->assertEquals(
            [0 => 1,2 => 2,3 => 3],
            Sequence::of([1, null, 2, 3, null])->filterNotEmpty()->toArray()
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

    /**
     * @throws Exception
     */
    function testFirst()
    {
        $this->assertEquals(
            1,
            Sequence::of([1, 2, 3, 4, 5])->first()
        );
    }

    /**
     * @depends testFilter
     * @throws Exception
     */
    function testFirstFilter()
    {
        $this->assertEquals(
            2,
            Sequence::of([1, 2, 3, 4, 5])->first(function ($a) {
                return $a > 1;
            })
        );
    }

    function testSortBy()
    {
        $this->assertEquals(
            [0, 1, 2, 3, 4, 5],
            Sequence::of([1, 3, 2, 4, 5, 0])->sortBy(function ($a) {
                return $a;
            })->toArray()
        );
    }

    function testSortDescBy()
    {
        $this->assertEquals(
            [5, 4, 3, 2, 1, 0],
            Sequence::of([1, 3, 2, 4, 5, 0])->sortDescBy(function ($a) {
                return $a;
            })->toArray()
        );
    }

    /**
     * @throws Exception
     * @expectedException OutOfRangeException
     */
    function testFirstException()
    {
        Sequence::of([1, 2, 3, 4, 5])->first(function ($a) { return $a > 5; });
    }

    function testFirstOrNull()
    {
        $this->assertEquals(
            1,
            Sequence::of([1, 2, 3, 4, 5])->firstOrNull()
        );

        $this->assertEquals(
            null,
            Sequence::of([])->firstOrNull()
        );
    }

    /**
     * @throws Exception
     */
    function testLast()
    {
        $this->assertEquals(
            5,
            Sequence::of([1, 2, 3, 4, 5])->last()
        );
    }

    /**
     * @depends testFilter
     * @throws Exception
     */
    function testLastFilter()
    {
        $this->assertEquals(
            4,
            Sequence::of([1, 2, 3, 4, 5])->last(function ($a) {
                return $a < 5;
            })
        );
    }

    /**
     * @throws Exception
     * @expectedException OutOfRangeException
     */
    function testLastException()
    {
        Sequence::of([1, 2, 3, 4, 5])->last(function ($a) { return $a > 5; });
    }

    function testLastOrNull()
    {
        $this->assertEquals(
            5,
            Sequence::of([1, 2, 3, 4, 5])->lastOrNull()
        );

        $this->assertEquals(
            null,
            Sequence::of([])->lastOrNull()
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

    function testAny()
    {
        $this->assertEquals(
            true,
            Sequence::of([1, 2, 3])->any(function($a) { return $a > 1; })
        );
    }

    function testAnyFalse()
    {
        $this->assertEquals(
            false,
            Sequence::of([1, 2, 3])->any(function($a) { return $a < 1; })
        );
    }

    function testCount()
    {
        $this->assertEquals(
            3,
            Sequence::of([1, 2, 3])->count()
        );
    }

    /**
     * @depends testFilter
     */
    function testCountFilter()
    {
        $this->assertEquals(
            2,
            Sequence::of([1, 2, 3])->count(function ($a) {
                return $a > 1;
            })
        );
    }

    function testGroup()
    {
        $this->assertEquals(
            [
                0 => [2, 4],
                1 => [1, 3, 5]
            ],
            Sequence::of([1, 2, 3, 4, 5])->groupBy(function($a) { return $a['type']; })
        );
    }

    function testIndexOf()
    {
        $this->assertEquals(
            1,
            Sequence::of([1, 2, 3, 4, 5])->indexOf(function($a) { return $a == 2; })
        );

        $this->assertEquals(
            -1,
            Sequence::of([1, 2, 3, 4, 5])->indexOf(function($a) { return $a == 0; })
        );
    }
}
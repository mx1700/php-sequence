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

    function testInitGenerator()
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

    function testPlus()
    {
        $this->assertEquals(
            [1, 2, 3, 4],
            Sequence::of([1, 2])->concat([3, 4])->toArray()
        );
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

    function testEach()
    {
        $r = 0;
        Sequence::of([1, 2, 3])->each(function ($a) use (&$r) {
            $r += $a;
        });
        $this->assertEquals(6, $r);
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
            [0 => 1, 2 => 2, 3 => 3],
            Sequence::of([1, null, 2, 3, null])->filterNotEmpty()->toArray()
        );
    }

    /**
     * @depends testFilterNotEmpty
     */
    function testValues()
    {
        $this->assertEquals(
            [1, 2, 3],
            Sequence::of([1, null, 2, 3, null])->filterNotEmpty()->values()->toArray()
        );
    }

    function testKeys()
    {
        $this->assertEquals(
            [0, 2, 3],
            Sequence::of([1, null, 2, 3, null])->filterNotEmpty()->keys()->toArray()
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

    function testDistinctBy()
    {
        $this->assertEquals(
            [0 => 1, 2 => 3, 5 => 6, 8 => 9],
            Sequence::of([1, 2, 3, 4, 5, 6, 7, 8, 9])->distinctBy(function ($a) {
                return intval($a / 3);
            })->toArray()
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
        Sequence::of([1, 2, 3, 4, 5])->first(function ($a) {
            return $a > 5;
        });
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
        Sequence::of([1, 2, 3, 4, 5])->last(function ($a) {
            return $a > 5;
        });
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

    function testFlatMap()
    {
        $this->assertEquals(
            [1, 2, 2, 3, 3, 3],
            Sequence::of([1, 2, 3])->flatMap(function ($a) {
                return array_fill(0, $a, $a);
            })->toArray()
        );
    }

    function testProduct()
    {
        $this->assertEquals(
            [[1, 3], [1, 4], [2, 3], [2, 4]],
            Sequence::of([1, 2])->product([3, 4])->toArray()
        );
    }

    function testAll()
    {
        $this->assertEquals(
            true,
            Sequence::of([1, 2, 3])->all(function ($a) {
                return $a < 5;
            })
        );
    }

    function testAllFalse()
    {
        $this->assertEquals(
            false,
            Sequence::of([1, 2, 3])->all(function ($a) {
                return $a < 3;
            })
        );
    }

    function testAny()
    {
        $this->assertEquals(
            true,
            Sequence::of([1, 2, 3])->any(function ($a) {
                return $a > 1;
            })
        );
    }

    function testAnyFalse()
    {
        $this->assertEquals(
            false,
            Sequence::of([1, 2, 3])->any(function ($a) {
                return $a < 1;
            })
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
            Sequence::of([1, 2, 3, 4, 5])->groupBy(function ($a) {
                return $a % 2;
            })->toArray()
        );
    }

    function testGroupJoin()
    {
        $l = [11, 12, 13, 25, 21, 34, 30];
        $r = Sequence::of([1, 2, 3, 4])->groupJoin(
            $l,
            function ($a) {
                return intval($a / 10);
            },
            function ($a) {
                return $a;
            },
            function ($inner, Sequence $outGroup) {
                return [$inner, $outGroup->values()->toArray()];
            }
        )->toArray();

        $this->assertEquals(
            [
                [1, [11,12,13]],
                [2, [25, 21]],
                [3, [34, 30]],
                [4, []]
            ], $r
        );
    }

    function testJoin()
    {
        $inner = [2, 1, 3, 2, 5];
        $out = [1 => '一', 2 => '二', 3 => '三'];
        $s = Sequence::of($inner)
            ->join(
                $out,
                function ($out, $outKey) {
                    return $outKey;
                },
                function ($inner, $innerKey) {
                    return $inner;
                },
                function ($inner, $out) {
                    return [$inner, $out];
                }
            )
            ->toArray();

        $this->assertEquals(
            [
                [2, '二'],
                [1, '一'],
                [3, '三'],
                [2, '二']
            ],
            $s
        );
    }

    function testLeftJoin()
    {
        $inner = [[2], [1], [3], [2], [5]];
        $out = [[1, '一'], [2,'二'], [3,'三']];

        Sequence::of($inner)->flatMap(function ($inner) use($out) {
            $r = Sequence::of($out)->filter(function($out) use($inner) { return $out[0] == $inner[0]; });
            if($r->none()) {
                return [$inner[0], null];
            } else {
                yield from $r->map(function() {});
            }
        });

        $s = Sequence::of($inner)
            ->leftJoin(
                $out,
                function ($out, $outKey) {
                    return $out[0];
                },
                function ($inner, $innerKey) {
                    return $inner[0];
                },
                function ($inner, $out) {
                    return [$inner[0], $out[1] ?? null];
                }
            )
            ->toArray();

        $this->assertEquals(
            [
                [2, '二'],
                [1, '一'],
                [3, '三'],
                [2, '二'],
                [5, null],
            ],
            $s
        );
    }

    function testIndexOf()
    {
        $this->assertEquals(
            1,
            Sequence::of([1, 2, 3, 4, 5])->indexOf(function ($a) {
                return $a == 2;
            })
        );

        $this->assertEquals(
            -1,
            Sequence::of([1, 2, 3, 4, 5])->indexOf(function ($a) {
                return $a == 0;
            })
        );
    }

    /**
     * @depends testSkip
     */
    function testSum()
    {
        $this->assertEquals(
            12,
            Sequence::of([1, 2, 3, 4, 5])->skip(2)->sum()
        );
    }

    /**
     * @depends testSkip
     */
    function testMax()
    {
        {
            $this->assertEquals(
                5,
                Sequence::of([6, 2, 3, 5, 4])->skip(1)->max()
            );
        }
    }

    /**
     * @depends testSkip
     */
    function testMin()
    {
        $this->assertEquals(
            2,
            Sequence::of([1, 2, 3, 5, 4])->skip(1)->min()
        );
    }

    function testNone()
    {
        $this->assertEquals(
            true,
            Sequence::of([1])->skip(1)->none()
        );

        $this->assertEquals(
            false,
            Sequence::of([1, 2])->skip(1)->none()
        );

        $this->assertEquals(
            true,
            Sequence::of([1, 2])->none(function ($a) {
                return $a > 2;
            })
        );

        $this->assertEquals(
            false,
            Sequence::of([1, 2])->none(function ($a) {
                return $a >= 2;
            })
        );
    }
}
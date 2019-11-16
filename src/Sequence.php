<?php
namespace mx1700;

use ArrayIterator;
use Closure;
use Exception;
use Iterator;
use OutOfRangeException;

class Sequence implements \IteratorAggregate
{
    /**
     * @var Iterator
     */
    private $source;

    /**
     * Sequence constructor.
     * @param Iterator|array $iterator
     */
    public function __construct($iterator)
    {
        if (is_array($iterator)) {
            $iterator = new ArrayIterator($iterator);
        }
        $this->source = $iterator;
    }

    /**
     * @param Iterator|array $iterator
     * @return Sequence
     */
    static function of($iterator): self
    {
        return new self($iterator);
    }

    /**
     * @param Iterator|array $iterator
     * @return Sequence
     */
    public function concat($iterator): self
    {
        $iterator = function () use ($iterator) {
            foreach ($this->source as $key => $item) {
                yield $item;
            }

            foreach ($iterator as $key => $item) {
                yield $item;
            }
        };
        return new self($iterator());
    }

    /**
     * @param Closure $fun
     * @return Sequence
     */
    public function map(Closure $fun): self
    {
        $iterator = function () use ($fun) {
            foreach ($this->source as $key => $item) {
                yield $key => $fun($item, $key);
            }
        };
        return new self($iterator());
    }

    /**
     * @param Closure $action
     * @return void
     */
    public function each(Closure $action)
    {
        foreach ($this->source as $key => $val) {
            $action($val, $key);
        }
    }

    /**
     * @param Closure $fun
     * @return Sequence
     */
    public function filter(Closure $fun): self
    {
        $iterator = function () use ($fun) {
            foreach ($this->source as $key => $item) {
                if ($fun($item, $key)) {
                    yield $key => $item;
                }
            }
        };

        return new self($iterator());
    }

    /**
     * @return Sequence
     */
    public function filterNotEmpty(): self
    {
        return $this->filter(function ($a) {
            return !empty($a);
        });
    }

    /**
     * @return Sequence
     */
    public function values(): self
    {
        $iterator = function () {
            foreach ($this->source as $item) {
               yield $item;
            }
        };

        return new self($iterator());
    }

    /**
     * @return Sequence
     */
    public function keys(): self
    {
        $iterator = function () {
            foreach ($this->source as $key => $item) {
                yield $key;
            }
        };

        return new self($iterator());
    }

    /**
     * @param int $n
     * @return Sequence
     */
    public function skip(int $n): self
    {
        $iterator = function () use ($n) {
            $i = 0;
            foreach ($this->source as $key => $item) {
                if ($i >= $n) {
                    yield $key => $item;
                }
                $i++;
            }
        };

        return new self($iterator());
    }

    /**
     * @param $count
     * @return Sequence
     */
    public function limit(int $count): self
    {
        $iterator = function () use ($count) {
            $i = 1;
            foreach ($this->source as $key => $item) {
                if ($i > $count) {
                    break;
                }
                yield $key => $item;
                $i++;
            }
        };

        return new self($iterator());
    }

    /**
     * @param Closure $selector
     * @return Sequence
     */
    public function distinctBy(Closure $selector)
    {
        $iterator = function () use ($selector) {
            $exists = [];
            foreach ($this->source as $key => $val) {
                $k = $selector($val, $key);
                if (!isset($exists[$k])) {
                    $exists[$k] = true;
                    yield $key => $val;
                }
            }
        };

        return new self($iterator());
    }

    /**
     * @param Closure $comparator
     * @return Sequence
     */
    public function sortWith(Closure $comparator)
    {
        $list = iterator_to_array($this->source);
        usort($list, $comparator);
        return new self($list);
    }

    /**
     * @param Closure $comparator
     * @return Sequence
     */
    public function sortBy(Closure $comparator)
    {
        return $this->sortWith(function ($a, $b) use ($comparator) {
            $av = $comparator($a);
            $bv = $comparator($b);
            if ($av == $bv) return 0;
            if ($av < $bv) return -1;
            return 1;
        });
    }

    /**
     * @param Closure $comparator
     * @return Sequence
     */
    public function sortDescBy(Closure $comparator)
    {
        return $this->sortWith(function ($a, $b) use ($comparator) {
            $av = $comparator($a);
            $bv = $comparator($b);
            if ($av == $bv) return 0;
            if ($av < $bv) return 1;
            return -1;
        });
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        $result = [];
        foreach ($this->source as $key => $item) {
            $result[$key] = $item;
        }
        return $result;
    }

    /**
     * @param Closure|null $fun
     * @return mixed
     * @throws Exception
     */
    public function first(Closure $fun = null)
    {
        if ($fun) {
            return $this->filter($fun)->first();
        }

        if (!$this->source->valid()) {
            throw new OutOfRangeException("Sequence is empty");
        }

        return $this->source->current();
    }

    /**
     * @param Closure|null $fun
     * @return mixed|null
     */
    public function firstOrNull(Closure $fun = null)
    {
        if ($fun) {
            return $this->filter($fun)->firstOrNull();
        }

        if (!$this->source->valid()) {
            return null;
        }

        return $this->source->current();
    }

    /**
     * @param Closure|null $fun
     * @return mixed
     * @throws Exception
     */
    public function last(Closure $fun = null)
    {
        if ($fun) {
            return $this->filter($fun)->last();
        }

        foreach ($this->source as $item) {
            $r = $item;
        }

        if (!isset($r)) {
            throw new OutOfRangeException("Sequence is empty");
        }

        return $r;
    }

    /**
     * @param Closure|null $fun
     * @return mixed|null
     */
    public function lastOrNull(Closure $fun = null)
    {
        if ($fun) {
            return $this->filter($fun)->lastOrNull();
        }

        foreach ($this->source as $item) {
            $r = $item;
        }

        if (!isset($r)) {
            return null;
        }

        return $r;
    }

    /**
     * @param Closure $action
     * @param null $initial
     * @return mixed|null
     */
    public function reduce(Closure $action, $initial = null)
    {
        $result = $initial;
        foreach ($this->source as $key => $value) {
            $result = $action($result, $value, $key);
        }
        return $result;
    }

    /**
     * @param Closure $action
     * @return array
     */
    public function flatMap(Closure $action)
    {
        $result = [];
        foreach ($this->source as $key => $value) {
            $list = $action($value, $key);
            if ($list) {
                $result = array_merge($result, $list);
            }
        }

        return $result;
    }

    /**
     * @param Closure $action
     * @return bool
     */
    public function all(Closure $action): bool
    {
        foreach ($this->source as $key => $value) {
            if (!$action($value, $key)) {
                return false;
            }
        }
        return true;
    }

    /**
     * @param Closure $action
     * @return bool
     */
    public function any(Closure $action): bool
    {
        foreach ($this->source as $key => $value) {
            if ($action($value, $key)) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param Closure|null $action
     * @return int
     */
    public function count(Closure $action = null): int
    {
        if ($action) {
            return $this->filter($action)->count();
        }

        return iterator_count($this->source);
    }

    public function groupBy(Closure $action): array
    {
        $result = [];
        foreach ($this->source as $key => $item) {
            $key = $action($item, $key);
            $result[$key][] = $item;
        }
        return $result;
    }

    public function indexOf(Closure $predicate)
    {
        foreach ($this->source as $key => $value) {
            if ($predicate($value, $key)) {
                return $key;
            }
        }

        return -1;
    }

    /**
     * @return float|int
     */
    public function sum()
    {
        return array_sum(iterator_to_array($this->source));
    }

    public function sumBy()
    {
        //todo
    }

    public function max()
    {
        return call_user_func_array('max', iterator_to_array($this->source));
    }

    public function maxBy()
    {
        //todo
    }

    public function maxWith()
    {

    }

    public function min()
    {
        return call_user_func_array('min', iterator_to_array($this->source));
    }

    public function minBy()
    {
        //todo
    }

    public function minWith()
    {
        //todo
    }

    /**
     * @param Closure|null $filter
     * @return bool
     */
    public function none(Closure $filter = null)
    {
        if ($filter) {
            return $this->filter($filter)->none();
        }
        return !$this->source->valid();
    }

    /**
     * Retrieve an external iterator
     * @link https://php.net/manual/en/iteratoraggregate.getiterator.php
     * @return \Traversable An instance of an object implementing <b>Iterator</b> or
     * <b>Traversable</b>
     * @since 5.0.0
     */
    public function getIterator()
    {
        return $this->source;
    }
}
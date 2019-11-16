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
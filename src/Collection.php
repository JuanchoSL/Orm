<?php

namespace JuanchoSL\Orm;

class Collection implements \Iterator, \JsonSerializable, \Countable
{

    private $var = array();

    public function insert($object): int
    {
        return array_push($this->var, $object);
    }

    public function rewind(): void
    {
        reset($this->var);
    }

    public function current(): mixed
    {
        return current($this->var);
    }

    public function first(): mixed
    {
        $this->rewind();
        return $this->current();
    }

    public function last(): mixed
    {
        return end($this->var);
    }

    public function key(): mixed
    {
        return key($this->var);
    }

    public function prev(): mixed
    {
        return prev($this->var);
    }
    public function next(): void
    {
        next($this->var);
    }

    public function valid(): bool
    {
        return ($this->key() !== NULL && $this->key() !== FALSE);
    }

    public function count(): int
    {
        return count($this->var);
    }

    public function hasElements(): bool
    {
        return ($this->count() > 0);
    }

    public function jsonSerialize(): mixed
    {
        $arr = array();
        foreach ($this->var as $vars) {
            $vars = get_object_vars($vars);
            foreach ($vars as &$var) {
                if (is_bool($var)) {
                    $var = (bool) $var;
                } else if (is_double($var)) {
                    $var = (double) $var;
                } else if (is_float($var)) {
                    $var = (float) $var;
                } else if (is_bool($var) || is_numeric($var)) {
                    $var = (int) $var;
                } else if (is_string($var)) {
                    $encoding = mb_detect_encoding($var);
                    if ($encoding !== 'utf-8') {
                        $var = mb_convert_encoding($var, 'utf-8', $encoding);
                    }
                }
            }
            $arr[] = $vars;
        }
        return $arr;
    }

    public function getCollection(): array
    {
        return $this->var;
    }

    public function get()
    {
        $return = $this->current();
        $this->next();
        return $return;
    }

}

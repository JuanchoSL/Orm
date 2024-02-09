<?php

namespace JuanchoSL\Orm;
use JuanchoSL\DataTransfer\Repositories\BaseCollectionable;

class Collection extends BaseCollectionable implements \Iterator, \JsonSerializable, \Countable
{

    public function insert($object): int
    {
        return array_push($this->data, $object);
    }

    public function first(): mixed
    {
        $this->rewind();
        return $this->current();
    }

    public function last(): mixed
    {
        return end($this->data);
    }

    public function prev(): mixed
    {
        return prev($this->data);
    }

    public function hasElements(): bool
    {
        return ($this->count() > 0);
    }

    public function jsonSerialize(): mixed
    {
        $arr = array();
        foreach ($this->data as $vars) {
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
        return $this->data;
    }

    public function get()
    {
        $return = $this->current();
        $this->next();
        return $return;
    }

}

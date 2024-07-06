<?php

namespace JuanchoSL\Orm;

use JuanchoSL\Orm\Datamodel\DataModelInterface;

class Collection extends \JuanchoSL\DataTransfer\Collection
{

    public function insert(DataModelInterface $object): mixed
    {
        $key = $object->{$object->getPrimaryKeyName()};
        return $this->data[$key] = $object;
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
    public function getIterator()
    {
        $iterator = new \JuanchoSL\DataTransfer\Collection();
        foreach ($this->data as $val) {
            $iterator->append($val);
        }
        return $iterator;
    }

}

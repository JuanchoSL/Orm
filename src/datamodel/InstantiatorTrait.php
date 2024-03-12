<?php

namespace JuanchoSL\Orm\datamodel;
use JuanchoSL\DataTransfer\Repositories\ArrayDataTransfer;

trait InstantiatorTrait
{

    public function __construct()
    {
        $this->values = new ArrayDataTransfer([]);
    }
    public static function model()
    {
        return get_called_class();
    }

    public static function getInstance()
    {
        $class = static::model();
        return new $class;
    }
    
    public static function make(iterable $values)
    {
        $instance = self::getInstance();
        return $instance->fill($values);
    }
}
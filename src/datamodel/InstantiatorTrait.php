<?php

namespace JuanchoSL\Orm\datamodel;

trait InstantiatorTrait
{

    public static function model()
    {
        return get_called_class();
    }

    public static function getInstance()
    {
        $class = static::model();
        return new $class;
    }
}
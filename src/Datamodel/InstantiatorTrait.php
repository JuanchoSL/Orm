<?php

declare(strict_types=1);

namespace JuanchoSL\Orm\Datamodel;

use JuanchoSL\DataTransfer\Repositories\ArrayDataTransfer;

trait InstantiatorTrait
{

    public function __construct()
    {
        $this->values = new ArrayDataTransfer([]);
    }
    public static function model(): string
    {
        return get_called_class();
    }

    public static function getInstance(): DataModelInterface
    {
        $class = static::model();
        return new $class;
    }

    public static function make(iterable $values): DataModelInterface
    {
        $instance = self::getInstance();
        return $instance->fill($values);
    }

    public function __sleep(): array
    {
        //$this->save();
        return ['identifier', 'values', 'loaded'];
    }
    
    public function __clone()
    {
        $this->values = clone $this->values;
    }
}
<?php

namespace JuanchoSL\Orm\engine\Relations;

use JuanchoSL\Orm\Collection;
use JuanchoSL\Orm\datamodel\DataModelInterface;
use JuanchoSL\Orm\datamodel\Model;
use JuanchoSL\Orm\querybuilder\QueryExecuter;

abstract class AbstractRelation
{
    protected Model $model;
    protected string $foreign_field;
    protected int $foreign_key;

    protected $relation;

    //abstract public function call();

    public function __call($method, $args)
    {
        $this->relation = call_user_func_array([$this->relation, $method], $args);
        if ($this->relation instanceof QueryExecuter) {
            return $this;
        }
        return $this->relation;
    }
}
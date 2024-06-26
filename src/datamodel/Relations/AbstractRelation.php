<?php

declare(strict_types=1);

namespace JuanchoSL\Orm\Datamodel\Relations;

use JuanchoSL\Orm\Datamodel\Model;
use JuanchoSL\Orm\querybuilder\QueryExecuter;

abstract class AbstractRelation
{
    protected Model $model;
    protected string $foreign_field;
    protected string $foreign_key;

    protected QueryExecuter $relation;

    public function __call($method, $args)
    {
        $relation = call_user_func_array([$this->relation, $method], $args);
        if ($relation instanceof QueryExecuter) {
            $this->relation = $relation;
            return $this;
        } else {
            return $relation;
        }
    }
}
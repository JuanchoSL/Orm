<?php

namespace JuanchoSL\Orm\querybuilder;
use JuanchoSL\Orm\querybuilder\Types\SelectQueryBuilder;

class QueryBuilder
{

    public QueryActionsEnum $operation;
    public $camps = ["*"];
    public string $table;
    public array $join = [];
    public array $condition = [];
    public $group;
    public $having;
    public array $values = [];
    public $order;
    public array $limit = [];
    public $extraQuery;

    public static function getInstance()
    {
        return new self;
    }
    public function clear(): static
    {
        $this->camps = [];
        $this->table = '';
        $this->join = [];
        $this->condition = [];
        $this->group = null;
        $this->having = null;
        $this->order = null;
        $this->limit = [];
        $this->values = [];
        $this->extraQuery = null;
        return $this;
    }
    public function doAction(QueryActionsEnum $action): static
    {
        $this->operation = $action;
        return $this;
    }

    public function setCamps(array $camps): static
    {
        $this->camps = $camps;
        return $this;
    }

    public function select(array $camps = array())
    {
        //return SelectQueryBuilder::getInstance()->select($camps);

        $this->doAction(QueryActionsEnum::SELECT);
        $this->setCamps($camps);
        return $this;
    }

    public function from(string $table): static
    {
        return $this->table($table);
    }

    public function into(string $table): static
    {
        return $this->table($table);
    }

    public function table(string $table): static
    {
        $this->table = $table;
        return $this;
    }

    public function join(array $inner): static
    {
        $this->join = $inner;
        return $this;
    }

    protected function values(array $values): static
    {
        $this->values = $values;
        return $this;
    }

    public function where(array ...$where): static
    {
        $args = func_get_args();
        if (!empty($args)) {
            $this->condition[] = ['AND' => $args];
        }
        return $this;
    }

    public function orWhere(array ...$where): static
    {
        $args = func_get_args();
        if (!empty($args)) {
            $this->condition[] = ['OR' => $args];
        }
        return $this;
    }

    public function groupBy($group): static
    {
        $this->group = $group;
        return $this;
    }

    public function having($having): static
    {
        $this->having = $having;
        return $this;
    }

    public function orderBy($order): static
    {
        $this->order = $order;
        return $this;
    }

    public function limit(int $limit, int $offset = 0): static
    {
        if (!empty($limit))
            $this->limit = [(int) $limit, (int) $offset];
        return $this;
    }

    public function insert(array $values): static
    {
        $this->doAction(QueryActionsEnum::INSERT);
        $this->values = $values;
        return $this;
    }

    public function update(array $values): static
    {
        $this->doAction(QueryActionsEnum::UPDATE);
        $this->values = $values;
        return $this;
    }

    public function delete(): static
    {
        $this->doAction(QueryActionsEnum::DELETE);
        return $this;
    }

    public function drop(): static
    {
        $this->doAction(QueryActionsEnum::DROP);
        return $this;
    }

    public function truncate(): static
    {
        $this->doAction(QueryActionsEnum::TRUNCATE);
        return $this;
    }

    public function extraQuery($str): static
    {
        $this->extraQuery = " " . $str;
        return $this;
    }

}

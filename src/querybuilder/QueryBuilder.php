<?php

namespace JuanchoSL\Orm\querybuilder;

class QueryBuilder
{

    const MODE_SELECT = "SELECT";
    const MODE_INSERT = "INSERT";
    const MODE_UPDATE = "UPDATE";
    const MODE_DELETE = "DELETE";
    const MODE_TRUNCATE = "TRUNCATE";
    const MODE_DROP = "DROP";

    public string $operation;
    public $camps = "*";
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
    public function clear(): self
    {
        $this->camps = null;
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
    public function doAction(string $action): self
    {
        $this->operation = $action;
        return $this;
    }

    public function setCamps(array $camps): self
    {
        $this->camps = $camps;
        return $this;
    }

    public function select(array $camps = array()): self
    {
        $this->doAction(self::MODE_SELECT);
        $this->setCamps($camps);
        return $this;
    }

    public function from(string $table): self
    {
        return $this->table($table);
    }

    public function into(string $table): self
    {
        return $this->table($table);
    }

    public function table(string $table): self
    {
        $this->table = $table;
        return $this;
    }

    public function join(array $inner): self
    {
        $this->join = $inner;
        return $this;
    }

    protected function values(array $values): self
    {
        $this->values = $values;
        return $this;
    }

    public function where(array ...$where): self
    {
        $this->condition[] = ['AND' => func_get_args()];
        return $this;
    }
    public function orWhere(array ...$where): self
    {
        $this->condition[] = ['OR' => func_get_args()];
        return $this;
    }

    public function groupBy($group): self
    {
        $this->group = $group;
        return $this;
    }

    public function having($having): self
    {
        $this->having = $having;
        return $this;
    }

    public function orderBy($order): self
    {
        $this->order = $order;
        return $this;
    }

    public function limit(int $limit, int $offset = 0): self
    {
        if (!empty($limit))
            $this->limit = [(int) $limit, (int) $offset];
        return $this;
    }

    public function insert(array $values): self
    {
        $this->doAction(self::MODE_INSERT);
        $this->values = $values;
        return $this;
    }

    public function update(array $values): self
    {
        $this->doAction(self::MODE_UPDATE);
        $this->values = $values;
        return $this;
    }

    public function delete(): self
    {
        $this->doAction(self::MODE_DELETE);
        return $this;
    }

    public function drop(): self
    {
        $this->doAction(self::MODE_DROP);
        return $this;
    }

    public function truncate(): self
    {
        $this->doAction(self::MODE_TRUNCATE);
        return $this;
    }

    public function extraQuery($str): self
    {
        $this->extraQuery = " " . $str;
        return $this;
    }

}

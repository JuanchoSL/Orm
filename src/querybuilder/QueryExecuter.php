<?php

namespace JuanchoSL\Orm\querybuilder;

use JuanchoSL\Orm\Collection;
use JuanchoSL\Orm\datamodel\DataModelInterface;
use JuanchoSL\Orm\engine\Cursors\CursorInterface;
use JuanchoSL\Orm\engine\Drivers\DbInterface;

class QueryExecuter
{

    private DataModelInterface $response_model;
    private DbInterface $conn;
    private QueryBuilder $query_builder;

    public function __construct(DbInterface $connection, DataModelInterface $response_model)
    {
        $this->query_builder = new QueryBuilder();
        $this->conn = $connection;
        $this->response_model = $response_model;
    }

    public function __call(string $function, array $arguments): self
    {
        $this->query_builder = call_user_func_array([$this->query_builder, $function], $arguments);
        return $this;
    }

    public function delete(): int
    {
        $this->query_builder->delete();
        $this->cursor();
        return $this->conn->affectedRows();
    }

    public function truncate(): bool
    {
        $this->conn->setTable($this->query_builder->table);
        return $this->conn->truncate();
    }

    public function save(): int
    {
        $this->conn->setTable($this->query_builder->table);
        return $this->conn->insert(current($this->query_builder->values));
        /*
        $this->query_builder->insert(current($this->query_builder->values));
        $this->cursor();
        return $this->conn->lastInsertedId();
        */
    }
    public function update(array $new_data): int
    {
        $this->query_builder->update($new_data);
        $this->cursor();
        return $this->conn->affectedRows();
    }
    public function first(): DataModelInterface
    {
        return $this->get()->current();
    }
    public function last(): DataModelInterface
    {
        return $this->get()->last();
        /*
        $collection = $this->get()->getCollection();
        return end($collection);
        */
    }

    public function get(): Collection
    {
        $response = new Collection();
        $cursor = $this->cursor();
        while (!empty($element = $cursor->next())) {
            if (count(get_object_vars($element)) > 1) {
                $response->insert($this->response_model->make((array)$element));
            } else {
                $response->insert($this->response_model->findByPk($element->{$this->response_model->getPrimaryKeyName()}));
            }
        }
        return $response;
    }

    public function count(): int
    {
        return $this->cursor()->count();
    }

    protected function cursor(): CursorInterface
    {
        return $this->conn->execute($this->query_builder);
    }

}
<?php

namespace JuanchoSL\Orm\querybuilder;

use JuanchoSL\Orm\Collection;
use JuanchoSL\Orm\Datamodel\DataModelInterface;
use JuanchoSL\Orm\engine\Cursors\CursorInterface;
use JuanchoSL\Orm\engine\Drivers\DbInterface;
use JuanchoSL\Orm\engine\Responses\AlterResponse;
use JuanchoSL\Orm\engine\Responses\EmptyResponse;
use JuanchoSL\Orm\engine\Responses\InsertResponse;

class QueryExecuter
{

    private DataModelInterface $response_model;

    private DbInterface $conn;

    private QueryBuilder $query_builder;

    public function __construct(DbInterface $connection, DataModelInterface $response_model)
    {
        $this->query_builder = new QueryBuilder();
        $this->query_builder = $this->query_builder->table($response_model->getTableName());
        $this->conn = $connection;
        $this->response_model = $response_model;
    }

    public function __call(string $function, array $arguments): self
    {
        $this->query_builder = call_user_func_array([$this->query_builder, $function], $arguments);
        return $this;
    }

    public function delete(): AlterResponse
    {
        $this->query_builder->delete();
        return $this->cursor();
    }

    public function truncate(): EmptyResponse
    {
        $this->query_builder->truncate();
        return $this->cursor();
    }

    public function insert(array $new_data): InsertResponse
    {
        $this->query_builder->insert($new_data);
        return $this->cursor();
    }

    public function update(array $new_data): AlterResponse
    {
        $this->query_builder->update($new_data);
        return $this->cursor();
    }

    public function first(): DataModelInterface
    {
        $this->query_builder->limit(1);
        return $this->get()->current();
    }

    public function last(): DataModelInterface
    {
        return $this->get()->last();
    }

    public function get(): Collection
    {
        $this->query_builder->select();
        $cursor = $this->cursor();
        $response = new Collection();
        while (!empty($element = $cursor->next())) {
            if (count(get_object_vars($element)) > 1) {
                $response->insert($this->response_model->make((array) $element));
            } else {
                $response->insert($this->response_model->findByPk($element->{$this->response_model->getPrimaryKeyName()}));
            }
        }
        $cursor->free();
        return $response;
    }

    public function count(): int
    {
        return $this->cursor()->count();
    }

    protected function cursor(): CursorInterface|AlterResponse|InsertResponse|EmptyResponse
    {
        return $this->conn->execute($this->query_builder);
    }

}
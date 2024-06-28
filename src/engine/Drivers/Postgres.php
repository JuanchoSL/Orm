<?php

namespace JuanchoSL\Orm\engine\Drivers;

use JuanchoSL\Orm\engine\Cursors\CursorInterface;
use JuanchoSL\Orm\engine\Cursors\PostgresCursor;
use JuanchoSL\Orm\engine\Responses\AlterResponse;
use JuanchoSL\Orm\engine\Responses\EmptyResponse;
use JuanchoSL\Orm\engine\Responses\InsertResponse;
use JuanchoSL\Orm\engine\Structures\FieldDescription;
use JuanchoSL\Orm\querybuilder\QueryActionsEnum;
use JuanchoSL\Orm\querybuilder\QueryBuilder;
use JuanchoSL\Orm\querybuilder\SQLBuilderTrait;
use JuanchoSL\Orm\querybuilder\Types\CreateQueryBuilder;

class Postgres extends RDBMS implements DbInterface
{

    use SQLBuilderTrait;

    protected $requiredModule = 'pgsql';

    public function connect(): void
    {
        $port = empty($this->credentials->getPort()) ? '5432' : $this->credentials->getPort();
        try {
            $this->linkIdentifier = pg_pconnect("host={$this->credentials->getHost()} port={$port} dbname={$this->credentials->getDataBase()} user={$this->credentials->getUsername()} password={$this->credentials->getPassword()} connect_timeout=5");
        } catch (\Exception $exception) {
            $this->log($exception, 'error', [
                'exception' => $exception,
                'credentials' => $this->credentials
            ]);
            throw $exception;
        }
    }

    public function disconnect(): bool
    {
        if (!empty($this->linkIdentifier)) {
            $result = pg_close($this->linkIdentifier);
        }
        $this->linkIdentifier = null;
        return $result ?? true;
    }

    public function getTables(): array
    {
        return parent::extractTables("SELECT tablename FROM pg_catalog.pg_tables");
    }

    protected function parseDescribe(QueryBuilder $sqlBuilder): string
    {
        return "select is_identity as key, column_name as field, udt_name as type, character_maximum_length as length, column_default as default, is_nullable as null from INFORMATION_SCHEMA.COLUMNS where table_name = '" . $sqlBuilder->table . "'";
    }

    protected function getParsedField(array $keys): FieldDescription
    {
        if (empty($keys['length'])) {
            preg_match('/([a-zA-Z]+)(\d?)/', $keys['type'], $matches);
            if (count($matches) >= 2) {
                $keys['type'] = $matches[1];
                $keys['length'] = $matches[2];
            }
        }
        $field = new FieldDescription;
        $field
            ->setName($keys['field'])
            ->setType($keys['type'])
            ->setLength($keys['length'] ?? null)
            ->setNullable($keys['null'] == 'YES')
            ->setDefault($keys['default'])
            ->setKey($keys['key'] == 'YES');
        return $field;
    }

    protected function query(string $query): CursorInterface|InsertResponse|AlterResponse|EmptyResponse
    {
        $cursor = pg_query($this->linkIdentifier, $query);
        if (!$cursor) {
            throw new \Exception(pg_last_error($this->linkIdentifier));
        }
        $action = QueryActionsEnum::make(strtoupper(substr($query, 0, strpos($query, ' '))));
        if ($action->isIterable() || stripos($query, "RETURNING") !== false) {
            $cursor = new PostgresCursor($cursor);
            /*} elseif ($action->isInsertable()) {
                $cursor = new InsertResponse($this->lastInsertedId());*/
        } elseif ($action->isAlterable()) {
            $cursor = new AlterResponse(pg_affected_rows($cursor));
        } else {
            $cursor = new EmptyResponse(pg_affected_rows($cursor) >= 0);
        }
        return $cursor;
    }

    public function escape(string $str): string
    {
        return pg_escape_string($this->linkIdentifier, stripcslashes($str));
    }

    protected function processInsert(QueryBuilder $builder): InsertResponse
    {
        if (empty($builder->extraQuery)) {
            $builder = $builder->extraQuery("RETURNING Currval('" . $builder->table . "_id_seq')");
            $res = $this->execute($this->getQuery($builder));
            $result = $res->next(static::RESPONSE_ROWS)[0];
        } else {
            $this->execute($this->getQuery($builder));
            $res = $this->execute(QueryBuilder::getInstance()->select(["max(id) as id"])->from($builder->table));
            $result = $res->next(static::RESPONSE_OBJECT)->id;
        }
        $res->free();
        return new InsertResponse($result);
    }

    protected function processTruncate(QueryBuilder $builder): EmptyResponse
    {
        $table = $builder->table;
        $result = $this->execute($this->getQuery($builder));
        $this->execute("ALTER SEQUENCE {$table}_id_seq RESTART WITH 1");
        return $result;
    }

    protected function mountLimit(int $limit, int $page): string
    {
        return " LIMIT " . $limit . " OFFSET " . (intval($page) * $limit);
    }

    protected function parseCreate(QueryBuilder $builder)
    {
        $sql = "CREATE TABLE %s (";
        foreach ($builder->values as $field) {
            $sql .= "{$field->getName()} {$field->getType()}";
            if ($field->isKey()) {
                $sql .= " PRIMARY KEY GENERATED ALWAYS AS IDENTITY";
            } else {
                $sql .= "({$field->getLength()})";
            }
            if (!$field->isNullable()) {
                $sql .= " NOT NULL";
            }
            $sql .= ",";
        }
        $sql = rtrim($sql, ',');
        $sql .= ")";
        return sprintf($sql, $builder->table);
    }
}

<?php

namespace JuanchoSL\Orm\Engine\Drivers;

use JuanchoSL\Orm\DatabaseFactory;
use JuanchoSL\Orm\engine\Cursors\CursorInterface;
use JuanchoSL\Orm\engine\Cursors\PostgresCursor;
use JuanchoSL\Orm\engine\Structures\FieldDescription;
use JuanchoSL\Orm\querybuilder\QueryBuilder;
use JuanchoSL\Orm\querybuilder\SQLBuilderTrait;

class Postgres extends RDBMS implements DbInterface
{

    use SQLBuilderTrait;

    protected $requiredModule = 'pgsql';

    public function connect(): void
    {
        $port = empty($this->credentials->getPort()) ? '5432' : $this->credentials->getPort();
        $this->linkIdentifier = pg_pconnect("host={$this->credentials->getHost()} port={$port} dbname={$this->credentials->getDataBase()} user={$this->credentials->getUsername()} password={$this->credentials->getPassword()} connect_timeout=5");
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

    public function insert(array $values): int
    {
        $builder = DatabaseFactory::queryBuilder()->insert($values)->into($this->getTable())->extraQuery("RETURNING Currval('" . $this->tabla . "_id_seq')");
        $result = $this->execute($builder);
        return $this->lastInsertedId = $result->next(self::RESPONSE_ROWS)[0];
    }

    public function describe(string $tabla = null): array
    {
        if (empty($tabla)) {
            $tabla = $this->tabla;
        }
        $describe = [];
        if (!empty($tabla)) {
            //$this->describe = array();
            //$result = $this->execute("select is_identity as key, column_name as field, data_type as type, character_maximum_length as length, column_default as default, is_nullable as null from INFORMATION_SCHEMA.COLUMNS where table_name = '" . $this->tabla . "'");
            $result = $this->execute("select is_identity as key, column_name as field, udt_name as type, character_maximum_length as length, column_default as default, is_nullable as null from INFORMATION_SCHEMA.COLUMNS where table_name = '" . $tabla . "'");
            while ($keys = $result->next(self::RESPONSE_ASSOC)) {
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
                $describe[$keys['field']] = $field;
            }
            $this->describe[$tabla] = $describe;
            if ($result) {
                $result->free();
            }
        }
        return $this->describe[$tabla];
    }

    public function execute(QueryBuilder|string $query): CursorInterface
    {
        $query = $this->parseQuery($query);
        if (empty($this->linkIdentifier)) {
            $this->connect();
        }
        $this->cursor = pg_query($this->linkIdentifier, $query);
        if (!$this->cursor) {
            throw new \Exception($query . " -> " . pg_last_error($this->linkIdentifier));
        }
        return new PostgresCursor($this->cursor);
    }
    public function escape(string $str): string
    {
        return pg_escape_string($this->linkIdentifier, stripcslashes($str));
    }

    public function affectedRows(): int
    {
        return $this->nResults = pg_affected_rows($this->cursor);
    }

    public function lastInsertedId(): int
    {
        return $this->lastInsertedId;
    }

    public function truncate(): bool
    {
        parent::truncate();
        $this->execute("ALTER SEQUENCE {$this->tabla}_id_seq RESTART WITH 1");
        return true;
    }

    protected function mountLimit(int $limit, int $page): string
    {
        return (isset($limit) && (is_int($limit))) ? " LIMIT " . $limit . " OFFSET " . (intval($page) * $limit) : null;
    }
    public function createTable(string $table_name, FieldDescription ...$fields)
    {
        $sql = "CREATE TABLE %s (";
        foreach ($fields as $field) {
            $sql .= "{$field->getName()} {$field->getType()}";
            if ($field->isKey()) {
                $sql .= " PRIMARY KEY GENERATED ALWAYS AS IDENTITY";
            }else{
                $sql .="({$field->getLength()})";
            }
            if (!$field->isNullable()) {
                $sql .= " NOT NULL";
            }
            $sql .= ",";
        }
        $sql = rtrim($sql ,',');
        $sql .= ")";
        return $this->execute(sprintf($sql, $table_name));
    }
}

<?php

declare(strict_types=1);

namespace JuanchoSL\Orm\Engine\Drivers;

use JuanchoSL\Orm\Engine\Cursors\CursorInterface;
use JuanchoSL\Orm\Engine\Cursors\SQLiteCursor;
use JuanchoSL\Orm\Engine\Responses\AlterResponse;
use JuanchoSL\Orm\Engine\Responses\EmptyResponse;
use JuanchoSL\Orm\Engine\Responses\InsertResponse;
use JuanchoSL\Orm\Engine\Structures\FieldDescription;
use JuanchoSL\Orm\Querybuilder\QueryActionsEnum;
use JuanchoSL\Orm\Querybuilder\QueryBuilder;
use JuanchoSL\Orm\Engine\Traits\SQLBuilderTrait;

class Sqlite extends RDBMS implements DbInterface
{

    use SQLBuilderTrait;

    protected $requiredModule = 'sqlite3';

    public function connect(): void
    {
        if (!$this->linkIdentifier) {
            $fileDB = str_replace(DIRECTORY_SEPARATOR . DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR, $this->credentials->getHost() . DIRECTORY_SEPARATOR . $this->credentials->getDataBase());
            try {
                $this->linkIdentifier = new \SQLite3($fileDB, SQLITE3_OPEN_READWRITE, $this->credentials->getPassword());
            } catch (\Exception $exception) {
                $this->log($exception, 'error', [
                    'exception' => $exception,
                    'credentials' => $this->credentials
                ]);
                throw $exception;
            }
        }
    }

    public function disconnect(): bool
    {
        if (is_object($this->linkIdentifier)) {
            $result = $this->linkIdentifier->close();
        }
        $this->linkIdentifier = null;
        return $result ?? true;
    }

    public function getTables(): array
    {
        $builder = QueryBuilder::getInstance()->select(['name'])->from('sqlite_master')->where(['type', 'table']);
        return parent::extractTables($builder); //"SELECT name FROM sqlite_master WHERE type='table'"
    }

    protected function parseDescribe(QueryBuilder $sqlBuilder): string
    {
        return $this->getQuery(QueryBuilder::getInstance()->doAction(QueryActionsEnum::PRAGMA)->table("table_info('" . $sqlBuilder->table . "')"));
    }

    protected function getParsedField(array $keys): FieldDescription
    {
        if (empty($keys['length'])) {
            preg_match('/([a-zA-Z]+)\((\d*)\)/', $keys['type'], $matches);
            if (count($matches) >= 2) {
                $keys['type'] = $matches[1];
                $keys['length'] = $matches[2];
            }
        }
        $field = new FieldDescription;
        $field
            ->setName($keys['name'])
            ->setType($keys['type'] ?? '')
            ->setLength($keys['length'] ?? null)
            ->setNullable($keys['notnull'] == 0)
            ->setDefault($keys['dflt_value'])
            ->setDescription('')
            ->setKey($keys['pk'] == 1);
        return $field;
    }

    protected function run(string $query): CursorInterface|InsertResponse|AlterResponse|EmptyResponse
    {
        //Las consultas que devuelven resultados se deben hacer por query, el resto por exec
        $action = QueryActionsEnum::make(strtoupper(substr($query, 0, strpos($query, ' '))));
        $method = $action->isIterable() ? 'query' : 'exec';
        //$method = (in_array(substr($query, 0, 6), array('SELECT', 'PRAGMA'))) ? 'query' : 'exec';
        $cursor = $this->linkIdentifier->$method($query);
        if (!$cursor) {
            $e = new \Exception($this->linkIdentifier->lastErrorMsg(), $this->linkIdentifier->lastErrorCode());
            $this->log($e, 'error', ['exception' => $e, 'query' => $query]);
            throw $e;
        }
        if ($action->isIterable()) {
            $cursor = new SQLiteCursor($cursor);
        } elseif ($action->isInsertable()) {
            $cursor = new InsertResponse($this->linkIdentifier->lastInsertRowID());
        } elseif ($action->isAlterable()) {
            $cursor = new AlterResponse($this->linkIdentifier->changes());
        } else {
            $cursor = new EmptyResponse(true);
        }
        return $cursor;
    }

    public function escape(string $value): string
    {
        return $this->linkIdentifier->escapeString(stripslashes($value));
    }

    protected function processTruncate(QueryBuilder $builder): EmptyResponse
    {
        $result = $this->execute(QueryBuilder::getInstance()->delete()->from($builder->table));
        $result2 = $this->execute(QueryBuilder::getInstance()->delete()->from('sqlite_sequence')->where(['name', $builder->table]));
        $success = $result->count() > 0;
        //$result->free();
        //$result2->free();
        return new EmptyResponse($success);
    }

    protected function parseCreate(QueryBuilder $builder)
    {
        $sql = "CREATE TABLE %s (";
        foreach ($builder->values as $field) {
            $sql .= "{$field->getName()} {$field->getType()}";
            if ($field->isKey()) {
                $sql .= " PRIMARY KEY AUTOINCREMENT";
            } else {
                $sql .= "({$field->getLength()})";
            }
            if (!$field->isNullable()) {
                $sql .= " NOT NULL";
            }
            if (!empty($field->getDefault())) {
                $sql .= sprintf(" DEFAULT '%s'", $field->getDefault());
            }
            $sql .= ",";
            if (!empty($field->getDescription())) {
                $sql .= sprintf(" -- %s", $field->getDescription());
            }
        }
        $sql = rtrim($sql, ',');
        $sql .= ")";
        return sprintf($sql, $builder->table);
    }
}

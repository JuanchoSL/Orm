<?php

declare(strict_types=1);

namespace JuanchoSL\Orm\Engine\Drivers;

use JuanchoSL\Orm\Engine\Cursors\CursorInterface;
use JuanchoSL\Orm\Engine\Cursors\MysqlCursor;
use JuanchoSL\Orm\Engine\Parsers\MysqliParser;
use JuanchoSL\Orm\Engine\Responses\AlterResponse;
use JuanchoSL\Orm\Engine\Responses\EmptyResponse;
use JuanchoSL\Orm\Engine\Responses\InsertResponse;
use JuanchoSL\Orm\Engine\Structures\FieldDescription;
use JuanchoSL\Orm\Querybuilder\QueryActionsEnum;
use JuanchoSL\Orm\Querybuilder\QueryBuilder;
use JuanchoSL\Orm\Engine\Traits\SQLBuilderTrait;

class Mysqli extends RDBMS implements DbInterface
{

    use SQLBuilderTrait;

    protected $requiredModule = 'mysqli';

    public function connect(): void
    {
        try {
            $this->linkIdentifier = mysqli_connect($this->credentials->getHost(), $this->credentials->getUsername(), $this->credentials->getPassword(), $this->credentials->getDataBase(), $this->credentials->getPort())
                or throw new \Exception(mysqli_connect_error(), mysqli_connect_errno());
        } catch (\Exception $exception) {
            $this->log(mysqli_connect_error(), 'error', [
                'exception' => $exception,
                'credentials' => $this->credentials
            ]);
            throw $exception;
        }
    }

    public function disconnect(): bool
    {
        if (!empty($this->linkIdentifier)) {
            $result = mysqli_close($this->linkIdentifier);
        }
        $this->linkIdentifier = null;
        return $result ?? true;
    }

    public function getTables(): array
    {
        return parent::extractTables("SHOW TABLES FROM " . $this->credentials->getDataBase());
    }

    protected function getParsedField(array $keys): FieldDescription
    {
        //return MysqliParser::parseField($keys);
        
        $varchar = explode(' ', (string) str_replace(['(', ')'], ' ', $keys['Type']));
        $field = new FieldDescription;
        $field
            ->setName($keys['Field'])
            ->setType(trim($varchar[0]))
            ->setLength(trim($varchar[1] ?? '0'))
            ->setNullable($keys['Null'] != 'NO')
            ->setDefault($keys['Default'])
            ->setKey(!empty($keys['Key']) && strtoupper($keys['Key']) == 'PRI');
        return $field;
    }

    protected function query(string $query): CursorInterface|InsertResponse|AlterResponse|EmptyResponse
    {
        $cursor = mysqli_query($this->linkIdentifier, $query);
        if (!$cursor) {
            $e = new \Exception(mysqli_error($this->linkIdentifier), mysqli_errno($this->linkIdentifier));
            $this->log($e, 'error', ['exception' => $e, 'query' => $query]);
            throw $e;
        }
        $action = QueryActionsEnum::make(strtoupper(substr($query, 0, strpos($query, ' '))));
        if ($action->isIterable()) {
            $cursor = new MysqlCursor($cursor);
        } elseif ($action->isInsertable()) {
            $cursor = new InsertResponse(mysqli_insert_id($this->linkIdentifier));
        } elseif ($action->isAlterable()) {
            $cursor = new AlterResponse(mysqli_affected_rows($this->linkIdentifier));
        } else {
            $cursor = new EmptyResponse(mysqli_affected_rows($this->linkIdentifier) >= 0);
        }
        return $cursor;
    }

    public function escape(string $value): string
    {
        return mysqli_escape_string($this->linkIdentifier, stripslashes($value));
    }

    protected function parseCreate(QueryBuilder $builder)
    {
        $sql = "CREATE TABLE `%s` (";
        foreach ($builder->values as $field) {
            $sql .= "`{$field->getName()}` {$field->getType()}({$field->getLength()})";
            if (!$field->isNullable()) {
                $sql .= " NOT NULL";
            }
            if ($field->isKey()) {
                $sql .= " PRIMARY KEY AUTO_INCREMENT";
            }
            $sql .= ",";
        }
        $sql = rtrim($sql, ',');
        $sql .= ")";
        return sprintf($sql, $builder->table);
    }
}

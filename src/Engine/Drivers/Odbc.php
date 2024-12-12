<?php

declare(strict_types=1);

namespace JuanchoSL\Orm\Engine\Drivers;

use JuanchoSL\Orm\Engine\Cursors\CursorInterface;
use JuanchoSL\Orm\Engine\Cursors\OdbcCursor;
use JuanchoSL\Orm\Engine\Responses\AlterResponse;
use JuanchoSL\Orm\Engine\Responses\EmptyResponse;
use JuanchoSL\Orm\Engine\Responses\InsertResponse;
use JuanchoSL\Orm\Engine\Structures\FieldDescription;
use JuanchoSL\Orm\Querybuilder\QueryActionsEnum;
use JuanchoSL\Orm\Querybuilder\QueryBuilder;
use JuanchoSL\Orm\Engine\Traits\SQLBuilderTrait;

class Odbc extends RDBMS implements DbInterface
{

    use SQLBuilderTrait;

    protected $requiredModule = 'odbc';
    protected $dsn;
    protected $driver;
    public function connect(): void
    {
        //$this->dsn = "DRIVER={SQL Server};SERVER={$this->credentials->getHost()};DATABASE={$this->credentials->getDataBase()};";
        $this->dsn = $this->credentials->getHost();

        if (!empty($this->credentials->getDataBase()) && !str_contains($this->dsn, 'DATABASE')) {
            if (substr($this->dsn, -1) != ';') {
                $this->dsn .= ";";
            }
            $this->dsn .= "DATABASE=" . $this->credentials->getDataBase() . ";";
        }
        try {
            $this->linkIdentifier = odbc_connect($this->dsn, $this->credentials->getUsername(), $this->credentials->getPassword())
                or throw new \Exception(odbc_errormsg());
        } catch (\Exception $exception) {
            $this->log($exception, 'error', [
                'exception' => $exception,
                'credentials' => $this->credentials,
                'dsn' => $this->dsn
            ]);
            throw $exception;
        }
        if (str_starts_with($this->dsn, "DRIVER")) {
            $matches = [];
            preg_match("~^DRIVER={(.+)}~", $this->dsn, $matches);
            $this->driver = $matches[1];
        } else {
            $dss[] = odbc_data_source($this->linkIdentifier, SQL_FETCH_FIRST);
            while (!empty($ds = odbc_data_source($this->linkIdentifier, SQL_FETCH_NEXT))) {
                $dss[] = $ds;
            }
            foreach ($dss as $ds) {
                if ($ds['server'] == $this->dsn) {
                    $this->driver = $ds['description'];
                }
            }
            $this->log('datasource', 'debug', ['ds' => $dss]);
        }

    }

    public function disconnect(): bool
    {
        if (!empty($this->linkIdentifier)) {
            odbc_close($this->linkIdentifier);
        }
        unset($this->linkIdentifier);
        return true;
    }

    public function getTables(): array
    {
        if (!$this->linkIdentifier) {
            $this->connect();
        }
        $tables = array();
        $result = odbc_tables($this->linkIdentifier);
        while (odbc_fetch_row($result)) {
            if (odbc_result($result, "TABLE_TYPE") == "TABLE") {
                $tables[] = odbc_result($result, "TABLE_NAME");
            }
        }
        return $tables;
    }

    public function describe(string $tabla): array
    {
        if (!$this->linkIdentifier) {
            $this->connect();
        }
        $describe = [];
        $fields = [];
        $pks = [];
        $KeySel = @odbc_primarykeys($this->linkIdentifier, $this->credentials->getDataBase(), '', $tabla);
        while ($KeySel && ($KeyRec = odbc_fetch_array($KeySel))) {
            $this->log("Keys {table}", 'debug', ['table' => $tabla, 'response' => $KeyRec, 'fields' => $describe]);
            $pks[] = $KeyRec["COLUMN_NAME"];
        }

        $result = $this->execute(QueryBuilder::getInstance()->doAction(QueryActionsEnum::DESCRIBE)->table($tabla));
        while ($keys = $result->next(static::RESPONSE_ASSOC)) {
            $fields[] = $keys;
            $field = $this->getParsedField($keys);
            $field->setKey((strpos($keys['TYPE_NAME'], 'identity') > 0) || in_array($keys['COLUMN_NAME'], $pks));
            $describe[strtolower($field->getName())] = $field;
        }
        $result->free();
        $this->describe[strtolower($tabla)] = $describe;
        $this->log("Describe {table}", 'debug', ['table' => $tabla, 'response' => $fields, 'fields' => $describe]);
        unset($fields);
        unset($describe);
        return $this->describe[$tabla];
    }

    protected function processDescribe(QueryBuilder $queryBuilder): CursorInterface
    {
        if (!$this->linkIdentifier) {
            $this->connect();
        }
        $columns = odbc_columns($this->linkIdentifier, $this->credentials->getDataBase(), '', $queryBuilder->table);
        return new OdbcCursor($columns);
    }

    protected function getParsedField(array $keys): FieldDescription
    {
        $field = new FieldDescription;
        $field
            ->setName($keys['COLUMN_NAME'])
            ->setType((string) str_replace(" identity", "", $keys['TYPE_NAME']))
            ->setLength($keys['COLUMN_SIZE'])
            ->setNullable($keys['NULLABLE'] == 1)
            ->setDefault($keys['COLUMN_DEF'])
            ->setKey((strpos($keys['TYPE_NAME'], 'identity') > 0));
        return $field;
    }

    protected function run(string $query): CursorInterface|InsertResponse|AlterResponse|EmptyResponse
    {
        $action = QueryActionsEnum::make(strtoupper(substr($query, 0, strpos($query, ' '))));
        if ($action->isInsertable()) {
            $result_id = odbc_prepare($this->linkIdentifier, $query);
            $cursor = odbc_execute($result_id);
        } else {
            $cursor = odbc_exec($this->linkIdentifier, $query);
        }
        if (!$cursor) {
            $e = new \Exception(odbc_errormsg($this->linkIdentifier));
            $this->log($e, 'error', ['exception' => $e, 'query' => $query]);
            throw $e;
        }
        if ($action->isIterable()) {
            $cursor = new OdbcCursor($cursor);
        } elseif ($action->isInsertable()) {
            $cursor = new InsertResponse($this->lastInsertedId());
        } elseif ($action->isAlterable()) {
            $cursor = new AlterResponse(odbc_num_rows($cursor));
        } else {
            $cursor = new EmptyResponse(odbc_num_rows($cursor) !== false);
        }
        return $cursor;
    }

    protected function lastInsertedId(): string
    {
        $c = $this->execute('SELECT @@IDENTITY AS ID');
        $lastInsertedId = $c->next(static::RESPONSE_OBJECT)->ID;
        $c->free();
        return $lastInsertedId;
    }

    protected function parseSelect(QueryBuilder $sqlBuilder): string
    {
        if (!empty($sqlBuilder->limit) && in_array($this->driver, ['SQL Server'])) {
            $where = $this->mountWhere($sqlBuilder->condition, $sqlBuilder->table);
            $join = (count($sqlBuilder->join ?? []) > 0) ? implode(" ", $sqlBuilder->join) : "";
            $inicio = ($sqlBuilder->limit[1] * $sqlBuilder->limit[0]);
            $limit = $sqlBuilder->limit[0];
            $limit += $inicio;
            $inicio++;
            $order = 'id'; //empty($sqlBuilder->order) ? $this->keys()[0] : $sqlBuilder->order;
            $query = "SELECT * FROM (SELECT t.*, ROW_NUMBER() OVER (ORDER BY " . $order . ") AS MyRow FROM " . $sqlBuilder->table . " t " . $join . " " . $where . ") AS totalNoPagination WHERE MyRow BETWEEN " . $inicio . " AND " . $limit;
            return $query;
        } else {
            return $this->getQuery($sqlBuilder);
        }
    }
}

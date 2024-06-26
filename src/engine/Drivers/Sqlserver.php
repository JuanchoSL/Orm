<?php

namespace JuanchoSL\Orm\engine\Drivers;

use JuanchoSL\Orm\engine\Cursors\CursorInterface;
use JuanchoSL\Orm\engine\Cursors\SqlsrvCursor;
use JuanchoSL\Orm\engine\Responses\AlterResponse;
use JuanchoSL\Orm\engine\Responses\EmptyResponse;
use JuanchoSL\Orm\engine\Responses\InsertResponse;
use JuanchoSL\Orm\engine\Structures\FieldDescription;
use JuanchoSL\Orm\querybuilder\QueryActionsEnum;
use JuanchoSL\Orm\querybuilder\QueryBuilder;
use JuanchoSL\Orm\querybuilder\SQLBuilderTrait;

/**
 * Esta clase permite conectar e interactuar con una tabla específica
 * en un servidor SQL Server.
 * 
 * La clase está preparada para realizar las operaciones básicas en una tabla 
 * mssql, como insertar registros, actualizarlos, eliminarlos o vaciar una tabla.
 * Permite devolver un array con los nombres de las columnas de la tabla para,
 * por ejemplo, la autoconstrucción de formularios, así como sus claves primarias
 * 
 * Requiere de drivers específicos en el servidor para poder funcionar:
 * @link https://msdn.microsoft.com/en-us/library/cc296170.aspx
 * Librerias dll para agregar a apache y driver odbc. Sólo funciona desde servidores Windows
 * 
 * Para problemas con el login y acceso de cuentas de  usuario
 * @link https://technet.microsoft.com/es-es/library/ms188670(v=sql.105).aspx
 * 
 * @author Juan Sánchez Lecegui
 * @version 1.0.0
 */
class Sqlserver extends RDBMS implements DbInterface
{

    use SQLBuilderTrait;

    protected $requiredModule = 'sqlsrv';

    public function connect(): void
    {
        $host = $this->credentials->getHost();
        if (strpos($this->credentials->getHost(), ",") === false) {
            $host .= ', ';
            $host .= (!empty($this->credentials->getPort())) ? $this->credentials->getPort() : '1433';
        }
        try {
            $this->linkIdentifier = sqlsrv_connect($host, [
                'UID' => $this->credentials->getUsername(),
                'PWD' => $this->credentials->getPassword(),
                'Database' => $this->credentials->getDataBase(),
                'encrypt' => false,
                'ReturnDatesAsStrings' => true
            ]) or throw new \Exception(sqlsrv_errors()[0]['message']);
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
            $result = sqlsrv_close($this->linkIdentifier);
        }
        $this->linkIdentifier = null;
        return $result ?? true;
    }

    public function getTables(): array
    {
        //return parent::extractTables("SELECT table_name from {$this->credentials->getDataBase()}.INFORMATION_SCHEMA.TABLES");
        return parent::extractTables(QueryBuilder::getInstance()->select(['table_name'])->from($this->credentials->getDataBase() . ".INFORMATION_SCHEMA.TABLES"));
    }

    protected function parseDescribe(QueryBuilder $sqlBuilder): string
    {
        return $this->getQuery(QueryBuilder::getInstance()->doAction(QueryActionsEnum::EXEC)->setCamps(['sp_columns'])->from($sqlBuilder->table));
    }

    protected function getParsedField(array $keys): FieldDescription
    {
        $field = new FieldDescription;
        $field
            ->setName($keys['COLUMN_NAME'])
            ->setType((string) str_replace(" identity", "", $keys['TYPE_NAME']))
            ->setLength($keys['LENGTH'])
            ->setNullable($keys['NULLABLE'] == 0)
            ->setDefault($keys['COLUMN_DEF'])
            ->setKey((strpos($keys['TYPE_NAME'], 'identity') > 0));
        return $field;
    }

    protected function query(string $query): CursorInterface|InsertResponse|AlterResponse|EmptyResponse
    {
        $action = QueryActionsEnum::make(strtoupper(substr($query, 0, strpos($query, ' '))));
        $scroll = $action->isIterable() ? SQLSRV_CURSOR_CLIENT_BUFFERED : SQLSRV_CURSOR_FORWARD;
        $cursor = sqlsrv_query($this->linkIdentifier, $query, array(), array("Scrollable" => $scroll));
        if (!$cursor || !is_null(sqlsrv_errors())) {
            throw new \Exception(implode(PHP_EOL, current(sqlsrv_errors())));
        }
        if ($action->isIterable()) {
            $cursor = new SqlsrvCursor($cursor);
        } elseif ($action->isInsertable()) {
            $cursor = new InsertResponse($this->lastInsertedId());
        } elseif ($action->isAlterable()) {
            $cursor = new AlterResponse(sqlsrv_rows_affected($cursor));
        } else {
            $cursor = new EmptyResponse(sqlsrv_rows_affected($cursor) !== false);
        }
        return $cursor;
    }

    public function escape(string $value): string
    {
        $this->log(__FUNCTION__ . __LINE__, 'debug', ['value' => $value]);
        //$value = addslashes(stripslashes($value));
        $value = str_replace(["'", '"'], ["''", '""'], $value);
        $this->log(__FUNCTION__ . __LINE__, 'debug', ['value' => $value]);
        return $value;
    }

    protected function lastInsertedId(): int
    {
        //        $res = $this->execute("SELECT @@IDENTITY AS id");
        //$res = $this->execute("SELECT SCOPE_IDENTITY() as id");
        $res = $this->execute(QueryBuilder::getInstance()->select(["SCOPE_IDENTITY() as id"]));
        $lastInsertedId = $res->next(static::RESPONSE_OBJECT)->id;
        $res->free();
        return $lastInsertedId;
    }

    protected function parseSelect(QueryBuilder $sqlBuilder): string
    {
        if (!empty($sqlBuilder->limit)) {
            $where = $this->mountWhere($sqlBuilder->condition, $sqlBuilder->table);
            $join = (count($sqlBuilder->join ?? []) > 0) ? implode(" ", $sqlBuilder->join) : "";
            $inicio = ($sqlBuilder->limit[1] * $sqlBuilder->limit[0]);
            $limit = $sqlBuilder->limit[0];
            $limit += $inicio;
            $inicio++;
            $order = empty($sqlBuilder->order) ? current($this->keys($sqlBuilder->table)) : $sqlBuilder->order;
            $query = "SELECT * FROM (SELECT t.*, ROW_NUMBER() OVER (ORDER BY " . $order . ") AS MyRow FROM " . $sqlBuilder->table . " t " . $join . " " . $where . ") AS totalNoPagination WHERE MyRow BETWEEN " . $inicio . " AND " . $limit;
            return $query;
        } else {
            return $this->getQuery($sqlBuilder);
        }
    }
    public function createTable(string $table_name, FieldDescription ...$fields)
    {
        $sql = "CREATE TABLE %s (";
        foreach ($fields as $field) {
            $sql .= "{$field->getName()} {$field->getType()}";
            if ($field->isKey()) {
                $sql .= " IDENTITY(1,1) PRIMARY KEY";
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
        return $this->execute(sprintf($sql, $table_name));
    }
}

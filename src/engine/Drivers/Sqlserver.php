<?php

namespace JuanchoSL\Orm\engine\Drivers;

use JuanchoSL\Orm\engine\Cursors\CursorInterface;
use JuanchoSL\Orm\engine\Cursors\SqlsrvCursor;
use JuanchoSL\Orm\engine\Structures\FieldDescription;
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
        $this->linkIdentifier = sqlsrv_connect($host, [
            'UID' => $this->credentials->getUsername(),
            'PWD' => $this->credentials->getPassword(),
            'Database' => $this->credentials->getDataBase(),
            'encrypt' => false,
            'ReturnDatesAsStrings' => true
        ]) or throw new \Exception(sqlsrv_errors()[0]['message']);
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
        return parent::extractTables("SELECT table_name from {$this->credentials->getDataBase()}.INFORMATION_SCHEMA.TABLES");
    }

    public function describe(string $tabla = null): array
    {
        if (empty($tabla)) {
            $tabla = $this->tabla;
        }
        $describe = array();
        if (!empty($tabla)) {
            //   $this->describe = array();
            //$result = $this->execute(DatabaseFactory::queryBuilder()->doAction("EXEC")->setCamps(["sp_columns"])->from($this->tabla));
            $result = $this->execute("EXEC sp_columns " . $tabla);
            while ($keys = $result->next(self::RESPONSE_ASSOC)) {
                $field = new FieldDescription;
                $field
                    ->setName($keys['COLUMN_NAME'])
                    ->setType((string)str_replace(" identity", "", $keys['TYPE_NAME']))
                    ->setLength($keys['LENGTH'])
                    ->setNullable($keys['NULLABLE'] == 0)
                    ->setDefault($keys['COLUMN_DEF'])
                    ->setKey((strpos($keys['TYPE_NAME'], 'identity') > 0));
                $describe[$keys['COLUMN_NAME']] = $field;
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
        $query = $this->parseQuery($query); //print_r($query);
        $scroll = in_array(strtoupper(substr($query, 0, strpos($query, ' '))), ['INSERT', 'UPDATE', 'DELETE']) ? SQLSRV_CURSOR_FORWARD : SQLSRV_CURSOR_CLIENT_BUFFERED;
        $this->cursor = sqlsrv_query($this->linkIdentifier, $query, array(), array("Scrollable" => $scroll));
        if (!is_null(sqlsrv_errors())) {
            throw new \Exception($query . " -> " . implode(PHP_EOL, current(sqlsrv_errors())));
        }
        return new SqlsrvCursor($this->cursor);
    }

    public function escape(string $value): string
    {
        return addslashes(stripslashes($value));
    }

    public function lastInsertedId(): int
    {
        //        $res = $this->execute("SELECT @@IDENTITY AS id");
        $res = $this->execute("SELECT SCOPE_IDENTITY() as id");
        return $this->lastInsertedId = $res->next(self::RESPONSE_OBJECT)->id;
    }
    public function affectedRows(): int
    {
        return $this->nResults = sqlsrv_rows_affected($this->cursor);
    }

    protected function parseSelect(QueryBuilder $sqlBuilder): string
    {
        if (!empty($sqlBuilder->limit)) {
            $where = $this->mountWhere($sqlBuilder->condition);
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

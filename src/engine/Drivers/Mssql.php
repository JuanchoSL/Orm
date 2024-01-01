<?php

namespace JuanchoSL\Orm\Engine\Drivers;

use JuanchoSL\Orm\engine\Cursors\MssqlCursor;
use JuanchoSL\Orm\engine\Cursors\SQLiteCursor;
use JuanchoSL\Orm\engine\Structures\FieldDescription;
use JuanchoSL\Orm\querybuilder\SQLBuilderTrait;

/**
 * Esta clase permite conectar e interactuar con una tabla específica
 * en un fichero sqlite mediante SQLsite3.
 *
 * La clase está preparada para realizar las operaciones básicas en una tabla
 * sqlite, como insertar registros, actualizarlos, eliminarlos o vaciar una tabla.
 * Permite devolver un array con los nombres de las columnas de la tabla para,
 * por ejemplo, la autoconstrucción de formularios, así como sus claves primarias.
 *
 * @author Juan Sánchez Lecegui
 * @version 1.0.2
 */
class Mssql extends RDBMS implements DbInterface
{

    use SQLBuilderTrait;

    protected $requiredModule = 'mssql';

    public function connect()
    {
        $host = $this->credentials->getHost();
        if (strpos($this->credentials->getHost(), ":") === false) {
            $host .= (!empty($this->credentials->getPort())) ? $this->credentials->getPort() : ':1433';
        }
        $this->linkIdentifier = mssql_connect($host, $this->credentials->getUsername(), $this->credentials->getPassword(), $this->credentials->getDataBase()) or throw new \Exception(mysqli_connect_error());

        mssql_select_db($this->credentials->getDataBase(), $this->linkIdentifier) or throw new \Exception(mssql_get_last_message());

    }

    public function disconnect()
    {
        return mssql_close($this->linkIdentifier);
    }

    /**
     * Devuelve el listado de nombres de las tablas del servidor y esquema seleccionado
     * @return mixed Array cuyo contenido es el listado de nombres de las tablas del esquema
     */
    public function getTables()
    {
        return parent::extractTables("SELECT table_name from {$this->credentials->getDataBase()}.INFORMATION_SCHEMA.TABLES");
    }

    /**
     * Devuelve una matriz asociativa con la configuración de los campos de la 
     * tabla, tipos, claves, valores por defecto...
     * @return array Matrz asociativa con los parámetros de las columnas
     */
    public function describe()
    {
        if (empty($this->describe)) {
            $this->describe = array();
            $result = $this->execute("EXEC sp_columns " . $this->tabla);
            while ($keys = $result->next(self::RESPONSE_ASSOC)) {
                $field = new FieldDescription;
                $field
                    ->setName($keys['COLUMN_NAME'])
                    ->setType(str_replace(" identity", "", $keys['TYPE_NAME']))
                    ->setLength($keys['LENGTH'])
                    ->setNullable($keys['NULLABLE'] == 0)
                    ->setDefault($keys['COLUMN_DEF'])
                    ->setKey((strpos($keys['TYPE_NAME'], 'identity') > 0));
                $this->describe[$keys['COLUMN_NAME']] = $field;

            }
            if ($result) {
                $result->free();
            }
        }
        return $this->describe;
    }

    public function execute($query)
    {
        $query = $this->parseQuery($query);
        $this->cursor = mssql_query($query, $this->linkIdentifier);
        return new MssqlCursor($this->cursor);
    }
/*
    public function nextResult($queryResult, $typeReturn = null)
    {
        if (!$typeReturn) {
            $typeReturn = $this->typeReturn;
        }
        switch ($typeReturn) {
            case self::RESPONSE_OBJECT:
                return mssql_fetch_object($queryResult);

            case self::RESPONSE_ROWS:
                return mssql_fetch_row($queryResult);

            case self::RESPONSE_ASSOC:
            default:
                return mssql_fetch_assoc($queryResult);
        }
        return false;
    }

    public function freeResult($result)
    {
        mssql_free_result($result);
    }
*/
    /**
     * Escapa valores introducidos en campos de texto para incluir en consultas
     * @param string $value Campo insertado en un input
     * @return string Cadena escapada para evitar SQL Injection
     */
    public function escape($value)
    {
        return addslashes(stripslashes($value));
    }
/*
    public function getNResults()
    {
        return $this->nResults = mssql_num_rows($this->cursor);
    }

    public function affectedRows()
    {
        return $this->nResults = mssql_rows_affected($this->linkIdentifier);
    }
*/
    public function lastInsertedId()
    {
        $res = $this->execute("SELECT @@IDENTITY AS id");
        return $this->lastInsertedId = $res->next(self::RESPONSE_OBJECT)->id;
    }

    protected function parseSelect()
    {
        if (!empty($this->sqlBuilder->limit)) {
            $where = $this->mountWhere($this->sqlBuilder->where);
            $join = (count($this->sqlBuilder->inner) > 0) ? implode(" ", $this->sqlBuilder->inner) : "";
            $inicio = ($this->sqlBuilder->limit[1] * $this->sqlBuilder->limit[0]);
            $limit = $this->sqlBuilder->limit[0];
            $limit += $inicio;
            $inicio++;
            $order = empty($this->sqlBuilder->order) ? $this->keys()[0] : $this->sqlBuilder->order;
            return "SELECT * FROM (SELECT t.*, ROW_NUMBER() OVER (ORDER BY " . $order . ") AS MyRow FROM " . $this->tabla . " t " . $join . " " . $where . ") AS totalNoPagination WHERE MyRow BETWEEN " . $inicio . " AND " . $limit;
        } else {
            return $this->getQuery($this->sqlBuilder);
        }
    }

}

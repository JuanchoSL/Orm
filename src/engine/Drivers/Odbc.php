<?php

namespace JuanchoSL\Orm\engine\Drivers;

use JuanchoSL\Orm\engine\Cursors\CursorInterface;
use JuanchoSL\Orm\engine\Cursors\OdbcCursor;
use JuanchoSL\Orm\engine\Structures\FieldDescription;
use JuanchoSL\Orm\querybuilder\QueryBuilder;
use JuanchoSL\Orm\querybuilder\SQLBuilderTrait;

/**
 * Esta clase permite conectar e interactuar con una tabla específica
 * en un servidor MySQL.
 *
 * La clase está preparada para realizar las operaciones básicas en una tabla
 * mysql, como insertar registros, actualizarlos, eliminarlos o vaciar una tabla.
 * Permite devolver un array con los nombres de las columnas de la tabla para,
 * por ejemplo, la autoconstrucción de formularios, así como sus claves primarias.
 * Las operaciones se realizan mediante la librería mejorada MySQLi
 *
 * @author Juan Sánchez Lecegui
 * @version 1.1.0
 */
class Odbc extends RDBMS implements DbInterface
{

    use SQLBuilderTrait;

    protected $requiredModule = 'odbc';
    protected $dns;

    public function connect(): void
    {
        $this->dns = "DRIVER={SQL Server};SERVER={$this->credentials->getHost()};DATABASE={$this->credentials->getDataBase()};";

        try {
            $this->linkIdentifier = odbc_connect($this->dns, $this->credentials->getUsername(), $this->credentials->getPassword())
                or throw new \Exception(odbc_errormsg());
        } catch (\Exception $exception) {
            $this->log($exception, 'error', [
                'exception' => $exception,
                'credentials' => $this->credentials
            ]);
            throw $exception;
        }
    }

    /**
     * Cierra la conexión mediante el puntero pasado por parámetro
     */
    public function disconnect(): bool
    {
        if (!empty($this->linkIdentifier)) {
            odbc_close($this->linkIdentifier);
        }
        unset($this->linkIdentifier);
        return true;
    }

    /**
     * Devuelve el listado de nombres de las tablas del servidor y esquema seleccionado
     * @return mixed Array cuyo contenido es el listado de nombres de las tablas del esquema
     */
    public function getTables(): array
    {
        $tables = array();
        $result = odbc_tables($this->linkIdentifier);
        while (odbc_fetch_row($result)) {
            if (odbc_result($result, "TABLE_TYPE") == "TABLE") {
                $tables[] = odbc_result($result, "TABLE_NAME");
            }
        }
        return $tables;
    }

    public function describe(string $tabla = null): array
    {
        if (empty($tabla)) {
            $tabla = $this->tabla;
        }
        $describe = [];
        if (!empty($tabla)) {
            if (!$this->linkIdentifier) {
                $this->connect();
            }
            $columns = odbc_columns($this->linkIdentifier, $this->credentials->getDataBase(), '', $tabla);
            $cursor = new OdbcCursor($columns);
            while ($keys = $cursor->next(self::RESPONSE_ASSOC)) {
                $field = new FieldDescription;
                $field
                    ->setName($keys['COLUMN_NAME'])
                    ->setType((string) str_replace(" identity", "", $keys['TYPE_NAME']))
                    ->setLength($keys['COLUMN_SIZE'])
                    ->setNullable($keys['NULLABLE'] == 0)
                    ->setDefault($keys['COLUMN_DEF'])
                    ->setKey((strpos($keys['TYPE_NAME'], 'identity') > 0));
                $describe[$keys['COLUMN_NAME']] = $field;

            }
            $this->describe[$tabla] = $describe;
            $cursor->free();
        }
        return $this->describe[$tabla];
    }

    public function execute(QueryBuilder|string $query): CursorInterface
    {
        if (!$this->linkIdentifier) {
            $this->connect();
        }
        $query = $this->parseQuery($query);
        if (in_array(substr($query, 0, 6), [QueryBuilder::MODE_INSERT])) {
            $result_id = odbc_prepare($this->linkIdentifier, $query);
            $this->cursor = odbc_execute($result_id);
        } else {
            $this->cursor = odbc_exec($this->linkIdentifier, $query);
        }
        if (!$this->cursor) {
            $exception = new \Exception($query . " -> " . odbc_errormsg($this->linkIdentifier));
            $this->log($exception, 'error');
            throw $exception;
        } else {
            $this->log($query, 'info');
        }
        return new OdbcCursor($this->cursor);
    }

    public function affectedRows(): int
    {
        return $this->nResults = odbc_num_rows($this->cursor);
    }

    public function lastInsertedId(): int
    {
        $c = $this->execute('SELECT @@IDENTITY AS ID');
        return $c->next(self::RESPONSE_OBJECT)->ID;
    }

    protected function parseSelect(QueryBuilder $sqlBuilder): string
    {
        if (!empty($sqlBuilder->limit) && stripos($this->dns, 'SQL Server') !== false) {
            $where = $this->mountWhere($sqlBuilder->condition);
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

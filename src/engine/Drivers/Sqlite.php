<?php

namespace JuanchoSL\Orm\Engine\Drivers;

use JuanchoSL\Orm\DatabaseFactory;
use JuanchoSL\Orm\engine\Cursors\CursorInterface;
use JuanchoSL\Orm\engine\Cursors\SQLiteCursor;
use JuanchoSL\Orm\engine\Structures\FieldDescription;
use JuanchoSL\Orm\querybuilder\QueryBuilder;
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
class Sqlite extends RDBMS implements DbInterface
{

    use SQLBuilderTrait;

    protected $requiredModule = 'sqlite3';

    public function connect(): void
    {
        if (!$this->linkIdentifier) {
            $fileDB = str_replace(DIRECTORY_SEPARATOR . DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR, $this->credentials->getHost() . DIRECTORY_SEPARATOR . $this->credentials->getDataBase());
            $this->linkIdentifier = new \SQLite3($fileDB, SQLITE3_OPEN_READWRITE, $this->credentials->getPassword());
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
        $builder = DatabaseFactory::queryBuilder()->select(['name'])->from('sqlite_master')->where(['type', 'table']);
        return parent::extractTables($builder); //"SELECT name FROM sqlite_master WHERE type='table'"
    }

    public function describe(string $tabla = null): array
    {
        if (empty($tabla)) {
            $tabla = $this->tabla;
        }
        $describe = [];
        if (!empty($tabla)) {
            //$this->describe = array();
            $result = $this->execute("PRAGMA table_info('" . $tabla . "')");
            while ($keys = $result->next(self::RESPONSE_ASSOC)) {
                $field = new FieldDescription;
                $field
                    ->setName($keys['name'])
                    ->setType($keys['type'] ?? '')
                    ->setLength($keys['length'] ?? null)
                    ->setNullable($keys['notnull'] == 0)
                    ->setDefault($keys['dflt_value'])
                    ->setKey($keys['pk'] == 1);
                $describe[$keys['name']] = $field;
            }
            $this->describe[$tabla] = $describe;
            $result->free();
        }
        return $this->describe[$tabla];
    }

    public function execute(QueryBuilder|string $query): CursorInterface
    {
        $query = $this->parseQuery($query);
        //Las consultas que devuelven resultados se deben hacer por query, el resto por exec
        $method = (in_array(substr($query, 0, 6), array('SELECT', 'PRAGMA'))) ? 'query' : 'exec';
        $cursor = $this->linkIdentifier->$method($query);
        if (!$cursor) {
            throw new \Exception($query . " -> " . $this->linkIdentifier->lastErrorMsg(), $this->linkIdentifier->lastErrorCode());
        }
        return new SQLiteCursor($cursor);
    }


    public function escape(string $value): string
    {
        return $this->linkIdentifier->escapeString(stripslashes($value));
    }
    public function affectedRows(): int
    {
        return $this->nResults = $this->linkIdentifier->changes();
    }

    public function lastInsertedId(): int
    {
        return $this->lastInsertedId = $this->linkIdentifier->lastInsertRowID();
    }

    public function truncate(): bool
    {
        parent::delete(array());
        $this->execute(DatabaseFactory::queryBuilder()->delete()->from('sqlite_sequence')->where(['name', [$this->tabla]]));
        return true;
    }
    public function createTable(string $table_name, FieldDescription ...$fields)
    {
        $sql = "CREATE TABLE %s (";
        foreach ($fields as $field) {
            $sql .= "{$field->getName()} {$field->getType()}";
            if ($field->isKey()) {
                $sql .= " PRIMARY KEY AUTOINCREMENT";
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

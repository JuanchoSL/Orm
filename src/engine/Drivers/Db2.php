<?php

namespace JuanchoSL\Orm\engine\Drivers;

use JuanchoSL\Orm\engine\Cursors\CursorInterface;
use JuanchoSL\Orm\engine\Cursors\Db2Cursor;
use JuanchoSL\Orm\engine\Responses\AlterResponse;
use JuanchoSL\Orm\engine\Responses\EmptyResponse;
use JuanchoSL\Orm\engine\Responses\InsertResponse;
use JuanchoSL\Orm\engine\Structures\FieldDescription;
use JuanchoSL\Orm\querybuilder\QueryActionsEnum;
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
class Db2 extends RDBMS implements DbInterface
{
    use SQLBuilderTrait;
    protected $requiredModule = 'ibm_db2';

    public function connect(): void
    {
        $port = empty($this->credentials->getPort()) ? '50000' : $this->credentials->getPort();
        try {
            $this->linkIdentifier = db2_connect("DATABASE={$this->credentials->getDataBase()};HOSTNAME={$this->credentials->getHost()};PORT={$port};PROTOCOL=TCPIP;UID={$this->credentials->getUsername()};PWD={$this->credentials->getPassword()}", '', '')
                //$this->linkIdentifier = db2_connect($this->credentials->getDataBase(),$this->credentials->getUsername(),$this->credentials->getPassword())
                or throw new \Exception(db2_conn_errormsg(), db2_conn_error());
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
            $result = db2_close($this->linkIdentifier);
        }
        $this->linkIdentifier = null;
        return $result ?? true;
    }

    protected function setTable(string $tabla): static
    {
        $tabla = strtolower($tabla);
        if (!array_key_exists($tabla, $this->describe)) {
            $this->keys($tabla);
            $this->describe($tabla);
            $this->columns($tabla);
        }
        return $this;
    }

    public function keys(string $tabla): array
    {
        if (!$this->linkIdentifier) {
            $this->connect();
        }
        $keys = db2_primary_keys($this->linkIdentifier, strtoupper($this->credentials->getDataBase()), strtoupper($this->credentials->getUsername()), strtoupper($tabla));
        $result = new DB2Cursor($keys);
        $tabla = strtolower($tabla);
        $this->keys[$tabla] = [];
        while ($key = $result->next(static::RESPONSE_ASSOC)) {
            $this->keys[$tabla][$key['COLUMN_NAME']] = $key['PK_NAME'];
        }
        $this->log(__FUNCTION__, 'debug', ['keys' => $this->keys]);
        $result->free();
        return array_keys($this->keys[$tabla]);
    }

    public function getTables(): array
    {
        if (!$this->linkIdentifier) {
            $this->connect();
        }
        $tables = array();
        $result = db2_tables($this->linkIdentifier, strtoupper($this->credentials->getDataBase()), strtoupper($this->credentials->getUsername()), '%');
        $result = new Db2Cursor($result);
        while ($item = $result->next(RDBMS::RESPONSE_ASSOC)) {
            $tables[] = strtolower($item['TABLE_NAME']);
        }
        $result->free();
        $this->log(__FUNCTION__, 'debug', ['tables' => $tables]);
        return $tables;
    }


    protected function processDescribe(QueryBuilder $queryBuilder): CursorInterface
    {
        $this->keys(strtolower($queryBuilder->table));
        $columns = db2_columns($this->linkIdentifier, '', '%', strtoupper($queryBuilder->table), '%');
        return new DB2Cursor($columns);
    }

    protected function getParsedField(array $keys): FieldDescription
    {
        $this->log(__FUNCTION__, 'debug', ['parse' => $keys]);
        $field = new FieldDescription;
        $field
            ->setName($keys['COLUMN_NAME'])
            ->setType($keys['TYPE_NAME'])
            ->setLength($keys['COLUMN_SIZE'])
            ->setNullable($keys['NULLABLE'] <> 0)
            ->setDefault('')
            ->setKey(in_array($keys['COLUMN_NAME'], $this->keys(strtolower($keys['TABLE_NAME']))) ? $this->keys[strtolower($keys['TABLE_NAME'])][$keys['COLUMN_NAME']] : '');
        return $field;
    }

    protected function query(string $query): CursorInterface|InsertResponse|AlterResponse|EmptyResponse
    {
        //        $cursor = db2_query($this->linkIdentifier, $query);
//        if (db2_errno($this->linkIdentifier) > 0) {
//            Debug::error(db2_stmt_errormsg($this->linkIdentifier) . " -> " . $query);
//            return false;
//        }
/*
$cursor = db2_prepare($this->linkIdentifier, $query);
if (!$cursor || !db2_execute($cursor)) {
    throw new \Exception(db2_stmt_errormsg($this->linkIdentifier));
}*/
        $cursor = db2_exec($this->linkIdentifier, $query);
        if (!$cursor) {
            //throw new \Exception(db2_stmt_errormsg($this->linkIdentifier));
        }
        $action = QueryActionsEnum::make(strtoupper(substr($query, 0, strpos($query, ' '))));
        if ($action->isIterable()) {
            $cursor = new Db2Cursor($cursor);
        } elseif ($action->isInsertable()) {
            $cursor = new InsertResponse(db2_last_insert_id($this->linkIdentifier));
        } elseif ($action->isAlterable()) {
            $cursor = new AlterResponse(db2_num_rows($cursor));
        } else {
            $cursor = new EmptyResponse(true);
        }
        return $cursor;
    }

    public function escape(string $value): string
    {
        return db2_escape_string(stripslashes($value));
    }

    protected function processTruncate(QueryBuilder $builder): EmptyResponse
    {
        $table = $builder->table;
        $this->setTable($table);
        $result = $this->query($this->getQuery($builder) . " IMMEDIATE");
        $pk = (string) $this->describe[$table][strtolower(current($this->keys($table)))]->getName();
        $this->query("ALTER TABLE {$table} ALTER COLUMN $pk RESTART WITH 1");
        //$seq = (string) $this->describe[$table][strtolower(current($this->keys($table)))]->getKey();
        //$this->execute("ALTER SEQUENCE {$seq} RESTART WITH 1");
        return $result;
    }

    public function createTable(string $table_name, FieldDescription ...$fields)
    {
        /*CREATE TABLE DB2INST1.TEST (
    ID INTEGER NOT NULL GENERATED ALWAYS AS IDENTITY(START WITH 1 INCREMENT BY 1),
    TEST VARCHAR(16) NOT NULL,
    DATO VARCHAR(16) NOT NULL,
    PRIMARY KEY(ID)
);
*/
        $sql = "CREATE TABLE %s (";
        foreach ($fields as $field) {
            $sql .= "{$field->getName()} " . strtoupper($field->getType());
            if (!$field->isKey()) {
                $sql .= "({$field->getLength()})";
            }
            if (!$field->isNullable()) {
                $sql .= " NOT NULL";
            }
            if ($field->isKey()) {
                $sql .= " GENERATED ALWAYS AS IDENTITY (START WITH 1 INCREMENT BY 1)";
            } elseif (!empty($field->getDefault())) {
                $sql .= " DEFAULT {$field->getDefault()}";
            }
            $sql .= ", ";
        }
        $sql = rtrim($sql, ', ');
        $sql .= ")";
        return $this->execute(sprintf($sql, strtoupper($table_name)));
    }
}

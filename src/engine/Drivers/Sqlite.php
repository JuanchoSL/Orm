<?php

namespace JuanchoSL\Orm\engine\Drivers;

use JuanchoSL\Orm\engine\Cursors\CursorInterface;
use JuanchoSL\Orm\engine\Cursors\SQLiteCursor;
use JuanchoSL\Orm\engine\Responses\AlterResponse;
use JuanchoSL\Orm\engine\Responses\EmptyResponse;
use JuanchoSL\Orm\engine\Responses\InsertResponse;
use JuanchoSL\Orm\engine\Structures\FieldDescription;
use JuanchoSL\Orm\querybuilder\QueryActionsEnum;
use JuanchoSL\Orm\querybuilder\QueryBuilder;
use JuanchoSL\Orm\querybuilder\SQLBuilderTrait;
use JuanchoSL\Orm\querybuilder\Types\CreateQueryBuilder;

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
        $field = new FieldDescription;
        $field
            ->setName($keys['name'])
            ->setType($keys['type'] ?? '')
            ->setLength($keys['length'] ?? null)
            ->setNullable($keys['notnull'] == 0)
            ->setDefault($keys['dflt_value'])
            ->setKey($keys['pk'] == 1);
        return $field;
    }

    protected function query(string $query): CursorInterface|InsertResponse|AlterResponse|EmptyResponse
    {
        //Las consultas que devuelven resultados se deben hacer por query, el resto por exec
        $action = QueryActionsEnum::make(strtoupper(substr($query, 0, strpos($query, ' '))));
        $method = $action->isIterable() ? 'query' : 'exec';
        //$method = (in_array(substr($query, 0, 6), array('SELECT', 'PRAGMA'))) ? 'query' : 'exec';
        $cursor = $this->linkIdentifier->$method($query);
        if (!$cursor) {
            throw new \Exception($this->linkIdentifier->lastErrorMsg(), $this->linkIdentifier->lastErrorCode());
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
        $this->execute(QueryBuilder::getInstance()->delete()->from('sqlite_sequence')->where(['name', $builder->table]));
        return new EmptyResponse($result->count() > 0);
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
            $sql .= ",";
        }
        $sql = rtrim($sql, ',');
        $sql .= ")";
        return sprintf($sql, $builder->table);
    }
}

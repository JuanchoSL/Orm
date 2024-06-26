<?php

namespace JuanchoSL\Orm\engine\Drivers;

use JuanchoSL\Orm\engine\Cursors\CursorInterface;
use JuanchoSL\Orm\engine\Cursors\MysqlCursor;
use JuanchoSL\Orm\engine\Responses\AlterResponse;
use JuanchoSL\Orm\engine\Responses\EmptyResponse;
use JuanchoSL\Orm\engine\Responses\InsertResponse;
use JuanchoSL\Orm\engine\Structures\FieldDescription;
use JuanchoSL\Orm\querybuilder\QueryActionsEnum;
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
        $varchar = explode(' ', (string) str_replace(['(', ')'], ' ', $keys['Type']));
        $field = new FieldDescription;
        $field
            ->setName($keys['Field'])
            ->setType(trim($varchar[0]))
            ->setLength(trim($varchar[1] ?? '0'))
            ->setNullable($keys['Null'])
            ->setDefault($keys['Default'])
            ->setKey(!empty($keys['Key']) && strtoupper($keys['Key']) == 'PRI');
        return $field;
    }

    protected function query(string $query): CursorInterface|InsertResponse|AlterResponse|EmptyResponse
    {
        $cursor = mysqli_query($this->linkIdentifier, $query);
        if (!$cursor) {
            throw new \Exception(mysqli_error($this->linkIdentifier), mysqli_errno($this->linkIdentifier));
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

    public function createTable(string $table_name, FieldDescription ...$fields)
    {
        $sql = "CREATE TABLE `%s` (";
        foreach ($fields as $field) {
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
        return $this->execute(sprintf($sql, $table_name));
    }
}

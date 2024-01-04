<?php

namespace JuanchoSL\Orm\engine\Drivers;

use JuanchoSL\Orm\engine\Cursors\CursorInterface;
use JuanchoSL\Orm\engine\Cursors\MysqlCursor;
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
class Mysqli extends RDBMS implements DbInterface
{

    use SQLBuilderTrait;

    protected $requiredModule = 'mysqli';


    public function connect(): void
    {
        $this->linkIdentifier = mysqli_connect($this->credentials->getHost(), $this->credentials->getUsername(), $this->credentials->getPassword(), $this->credentials->getDataBase(), $this->credentials->getPort()) or throw new \Exception(mysqli_connect_error());
        if (mysqli_connect_errno() > 0) {
            throw new \Exception(mysqli_connect_error());
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

    public function describe(string $tabla = null): array
    {
        if (empty($tabla)) {
            $tabla = $this->tabla;
        }
        $describe = array();
        if (!empty($tabla)) {
            $result = $this->execute("DESCRIBE " . $tabla);
            while ($keys = $result->next(self::RESPONSE_ASSOC)) {
                list($type, $lenght) = explode(' ', (string) str_replace(['(', ')'], ' ', $keys['Type']));
                $field = new FieldDescription;
                $field
                    ->setName($keys['Field'])
                    ->setType(trim($type))
                    ->setLength(trim($lenght))
                    ->setNullable($keys['Null'])
                    ->setDefault($keys['Default'])
                    ->setKey(!empty($keys['Key']));
                $describe[$keys['Field']] = $field;
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
        $query = $this->parseQuery($query);
        $cursor = mysqli_query($this->linkIdentifier, $query);
        if ($error_number = mysqli_errno($this->linkIdentifier) > 0) {
            throw new \Exception($query . " -> " . mysqli_error($this->linkIdentifier), $error_number);
        }
        return new MysqlCursor($cursor);
    }

    public function escape(string $value): string
    {
        return mysqli_escape_string($this->linkIdentifier, stripslashes($value));
    }


    public function affectedRows(): int
    {
        return $this->nResults = mysqli_affected_rows($this->linkIdentifier);
    }

    public function lastInsertedId(): int
    {
        return $this->lastInsertedId = mysqli_insert_id($this->linkIdentifier);
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
        $sql = rtrim($sql ,',');
        $sql .= ")";
        return $this->execute(sprintf($sql, $table_name));
    }
}

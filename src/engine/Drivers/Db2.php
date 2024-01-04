<?php

namespace JuanchoSL\Orm\engine\Drivers;

use JuanchoSL\Orm\engine\Cursors\CursorInterface;
use JuanchoSL\Orm\engine\Cursors\Db2Cursor;
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
class Db2 extends RDBMS implements DbInterface
{
    use SQLBuilderTrait;
    protected $requiredModule = 'ibm_db2';

    public function connect(): void
    {
        $port = empty($this->credentials->getPort()) ? '50000' : $this->credentials->getPort();

        $this->linkIdentifier = db2_connect("DATABASE={$this->credentials->getDataBase()};HOSTNAME={$this->credentials->getHost()};PORT={$port};PROTOCOL=TCPIP;UID={$this->credentials->getUsername()};PWD={$this->credentials->getPassword()}", null, null) or throw new \Exception(db2_conn_errormsg());
        //$this->linkIdentifier = db2_connect($this->credentials->getHost(), $this->credentials->getUsername(), $this->credentials->getPassword(), $this->credentials->getDataBase(), $this->credentials->getPort()) or throw new \Exception(db2_conn_errormsg());
//        db2_select_db($this->linkIdentifier, $this->dataBase) or Debug::error("databaseError", "database", E_USER_ERROR);
        // return $this->linkIdentifier;
    }

    /**
     * Cierra la conexión mediante el puntero pasado por parámetro
     */
    public function disconnect(): bool
    {
        if (!empty($this->linkIdentifier)) {
            $result = db2_close($this->linkIdentifier);
        }
        $this->linkIdentifier = null;
        return $result ?? true;
    }

    /**
     * Devuelve el listado de nombres de las tablas del servidor y esquema seleccionado
     * @return mixed Array cuyo contenido es el listado de nombres de las tablas del esquema
     */
    public function getTables(): array
    {
        //db2_tables($this->linkIdentifier);
        return parent::extractTables("SHOW TABLES FROM " . $this->credentials->getDataBase());
    }

    public function describe(string $tabla = null): array
    {
        if (empty($tabla)) {
            $tabla = $this->tabla;
        }
        $describe = [];
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
        //        $this->cursor = db2_query($this->linkIdentifier, $query);
//        if (db2_errno($this->linkIdentifier) > 0) {
//            Debug::error(db2_stmt_errormsg($this->linkIdentifier) . " -> " . $query);
//            return false;
//        }
        $this->cursor = db2_prepare($this->linkIdentifier, $query);
        if ($this->cursor && db2_execute($this->cursor)) {
            return new Db2Cursor($this->cursor);
        }
        throw new \Exception($query . ' -> ' . db2_stmt_errormsg($this->linkIdentifier));
    }

    public function escape(string $value): string
    {
        return db2_escape_string(stripslashes($value));
    }

    public function affectedRows(): int
    {
        return $this->nResults = db2_num_rows($this->cursor);
    }

    public function lastInsertedId(): int
    {
        return $this->lastInsertedId = (int) db2_last_insert_id($this->linkIdentifier);
    }

}

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
            $this->linkIdentifier = db2_connect("DATABASE={$this->credentials->getDataBase()};HOSTNAME={$this->credentials->getHost()};PORT={$port};PROTOCOL=TCPIP;UID={$this->credentials->getUsername()};PWD={$this->credentials->getPassword()}", null, null)
                or throw new \Exception(db2_conn_errormsg());
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

    public function getTables(): array
    {
        //db2_tables($this->linkIdentifier);
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
            ->setKey(!empty($keys['Key']));
        return $field;
    }

    protected function query(string $query): CursorInterface|InsertResponse|AlterResponse|EmptyResponse
    {
        //        $cursor = db2_query($this->linkIdentifier, $query);
//        if (db2_errno($this->linkIdentifier) > 0) {
//            Debug::error(db2_stmt_errormsg($this->linkIdentifier) . " -> " . $query);
//            return false;
//        }
        $cursor = db2_prepare($this->linkIdentifier, $query);
        if (!$cursor || !db2_execute($cursor)) {
            throw new \Exception(db2_stmt_errormsg($this->linkIdentifier));
        }
        $action = QueryActionsEnum::make(strtoupper(substr($query, 0, strpos($query, ' '))));
        if ($action->isIterable()) {
            $cursor = new Db2Cursor($cursor);
        } elseif ($action->isInsertable()) {
            $cursor = new InsertResponse(db2_last_insert_id($this->linkIdentifier));
        } elseif ($action->isAlterable()) {
            $cursor = new AlterResponse(db2_num_rows($cursor));
        } else {
            $cursor = new EmptyResponse(db2_num_rows($cursor));
        }
        return $cursor;
    }

    public function escape(string $value): string
    {
        return db2_escape_string(stripslashes($value));
    }

}

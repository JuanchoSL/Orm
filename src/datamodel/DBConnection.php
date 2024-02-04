<?php

namespace JuanchoSL\Orm\datamodel;

use JuanchoSL\Orm\engine\Drivers\DbInterface;

/**
 * Abstracción para la conexión a diferentes SGBD.
 *
 * Permite abstraer las conexiones a bases de datos para limitar las posibles
 * modificaciones en el código fuente en caso de cambiar de sistema de almacenamiento.
 * El método conectar devuelve una instancia de la clase correcta indicada en el
 * parámetro $type. Sería el único cambio a realizar en caso de darse el caso.
 *
 * @author Juan Sánchez Lecegui
 * @version 1.0.2
 */
abstract class DBConnection
{

    static $conn = [];

    protected $connection_name = 'default';

    public static function setConnection(DbInterface $connection, string $conection_name = 'default'): DbInterface
    {
        return self::$conn[$conection_name] = $connection;
    }

    abstract public function getTableName();

    public function __call($method, $parameters)
    {
        self::$conn[$this->connection_name]->setTable($this->getTableName());
        return call_user_func_array(array(self::$conn[$this->connection_name], $method), $parameters);
    }


    protected function adapterIdentifier($id)
    {
        /*if (is_string($id)) {
            switch (strtolower(get_class(self::$conn))) {
                case strtolower(\remote\database\Mongo::class):
                    $id = new \MongoDB\BSON\ObjectId($id);
                    break;

                case strtolower(\remote\database\MongoClient::class):
                    $id = new \MongoId($id);
                    break;
            }
        }*/
        return $id;
    }

}

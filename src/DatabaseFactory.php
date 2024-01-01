<?php

namespace JuanchoSL\Orm;

use JuanchoSL\Orm\datamodel\DBConnection;
use JuanchoSL\Orm\engine\DbCredentials;
use JuanchoSL\Orm\engine\Drivers\Mysql;
use JuanchoSL\Orm\engine\Drivers\Mysqli;
use JuanchoSL\Orm\engine\Drivers\Mysqle;
use JuanchoSL\Orm\engine\Drivers\RDBMS;
use JuanchoSL\Orm\engine\Drivers\Sqlite;
use JuanchoSL\Orm\Engine\Drivers\Sqlserver;
use JuanchoSL\Orm\engine\Drivers\Sqlsrv;
use JuanchoSL\Orm\engine\Drivers\Postgres;
use JuanchoSL\Orm\engine\Drivers\Mssql;
use JuanchoSL\Orm\engine\Drivers\Msql;
use JuanchoSL\Orm\engine\Drivers\Oracle;
use JuanchoSL\Orm\engine\Drivers\Mongo;
use JuanchoSL\Orm\engine\Drivers\MongoClient;
use JuanchoSL\Orm\engine\Drivers\Elasticsearch;
use JuanchoSL\Orm\engine\Drivers\Odbc;
use JuanchoSL\Orm\engine\Drivers\Db2;
use JuanchoSL\Orm\engine\Engines;
use JuanchoSL\Orm\querybuilder\QueryBuilder;

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
class DatabaseFactory
{
/*
    const PHP_MIN_VERSION = "5.0.0";
    const TYPE_MYSQL = 'MYSQL';
    const TYPE_MARIADB = 'MARIADB';
    const TYPE_MYSQLI = 'MYSQLI';
    const TYPE_MYSQLE = 'MYSQLE';
    const TYPE_SQLITE = 'SQLITE';
    const TYPE_SQLSRV = 'SQLSRV';
    const TYPE_MSSQL = 'MSSQL';
    const TYPE_MSQL = 'MSQL';
    const TYPE_POSTGRE = 'POSTGRE';
    const TYPE_MONGO = 'MONGO';
    const TYPE_MONGOCLIENT = 'MONGOCLIENT';
    const TYPE_ORACLE = 'ORACLE';
    const TYPE_ELASTICSEARCH = 'ELASTICSEARCH';
    const TYPE_ODBC = 'ODBC';
    const TYPE_DB2 = 'DB2';
*/
    /**
     * Abstraer la conexión a un servidor de datos.
     * Podemos pasar un string con el nombre de la conexión a utilizar dentro
     * del fichero de configuraciones o un array asociativo con los valores
     * @internal Si usamos array asociativo para $server, debe tener la keys 'host','username',
     * 'password','database'.
     * @param string $type Nombre del sistema gestor a instanciar: mysql, mysqli, mssql, oracle
     * @param mixed $server Nombre del servidor en el fichero o array de parámetros
     * @param string $tabla Nombre de la tabla a utilizar
     * @param string $typeReturn Formato de retorno de los valores de las tablas,
     * array asociativo (assoc) u objeto (object)
     * @return object Recurso del objeto instanciado
     */
    public static function init(DbCredentials $credentials, Engines $type, $response = RDBMS::RESPONSE_OBJECT)
    {
        switch ($type) {
            case Engines::TYPE_MYSQLI:
                $resource = new Mysqli($credentials, $response);
                $resource->execute("SET NAMES 'utf8'");
                break;
            case Engines::TYPE_SQLITE:
                $resource = new Sqlite($credentials, $response);
                break;
            case Engines::TYPE_POSTGRE:
                $resource = new Postgres($credentials, $response);
                break;
            case Engines::TYPE_SQLSRV:
                $resource = new Sqlserver($credentials, $response);
                break;
            case Engines::TYPE_ORACLE:
                $resource = new Oracle($credentials, $response);
                break;
            case Engines::TYPE_ODBC:
                $resource = new Odbc($credentials, $response);
                break;
            /*
            case Engines::TYPE_MONGO:
                $resource = new Mongo($credentials, $response);
                break;
            case Engines::TYPE_MYSQL:
                $resource = new Mysql($credentials, $response);
                break;
            case Engines::TYPE_MARIADB:
                $resource = new MariaDb($credentials, $response);
                break;
            case Engines::TYPE_MYSQLE:
                $resource = new Mysqle($credentials, $response);
                $resource->execute("SET NAMES 'utf8'");
                break;
            case Engines::TYPE_DB2:
                $resource = new Db2($credentials, $response);
                break;
            case Engines::TYPE_MSSQL:
                $resource = new Mssql($credentials, $response);
                break;
            case Engines::TYPE_MSQL:
                $resource = new Msql($credentials, $response);
                break;
            case Engines::TYPE_MONGOCLIENT:
                $resource = new MongoClient($credentials, $response);
                break;
            case Engines::TYPE_ELASTICSEARCH:
                $resource = new Elasticsearch($credentials, $response);
                break;
            */
        }
        return DBConnection::setConnection($resource);
    }

    public static function queryBuilder(): QueryBuilder
    {
        return new QueryBuilder();
    }
}

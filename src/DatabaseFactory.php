<?php

namespace JuanchoSL\Orm;

use JuanchoSL\Exceptions\ServiceUnavailableException;
use JuanchoSL\Orm\datamodel\DBConnection;
use JuanchoSL\Orm\engine\DbCredentials;
use JuanchoSL\Orm\engine\Drivers\DbInterface;
use JuanchoSL\Orm\engine\Drivers\Mysqli;
use JuanchoSL\Orm\engine\Drivers\RDBMS;
use JuanchoSL\Orm\engine\Drivers\Sqlite;
use JuanchoSL\Orm\engine\Drivers\Sqlserver;
use JuanchoSL\Orm\engine\Drivers\Postgres;
use JuanchoSL\Orm\engine\Drivers\Oracle;
use JuanchoSL\Orm\engine\Drivers\Odbc;
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

    public static function init(DbCredentials $credentials, Engines $type, $response = RDBMS::RESPONSE_OBJECT):DbInterface
    {
        switch ($type) {
            case Engines::TYPE_MYSQLI:
                $resource = new Mysqli($credentials, $response);
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
            default:
                throw new ServiceUnavailableException("The service type {$type} is not available");
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

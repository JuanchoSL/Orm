<?php

namespace JuanchoSL\Orm\Tests;

use JuanchoSL\Orm\DatabaseFactory;
use JuanchoSL\Orm\engine\DbCredentials;
use JuanchoSL\Orm\Engine\Drivers\RDBMS;
use JuanchoSL\Orm\engine\Engines;

trait ConnectionTrait
{
    public function getConnection(Engines $connection_type)
    {
        switch ($connection_type) {
            case Engines::TYPE_MYSQLI:
                $credentials = new DbCredentials('localhost', 'test', 'test', 'test');
                break;

            case Engines::TYPE_SQLITE:
                $path = dirname(__DIR__, 1) . DIRECTORY_SEPARATOR . 'var';
                $credentials = new DbCredentials($path, '', '', 'test.db');
                break;

            case Engines::TYPE_POSTGRE:
                $credentials = new DbCredentials('localhost', 'root', 'root', 'test');
                break;

            case Engines::TYPE_SQLSRV:
                $credentials = new DbCredentials('localhost', 'sa', 'Administrador1', 'master');
                break;

            case Engines::TYPE_ORACLE:
                $credentials = new DbCredentials('localhost', 'SYS', 'oracle', 'SYSTEM');
                break;

            case Engines::TYPE_ODBC:
                $credentials = new DbCredentials('localhost', 'sa', 'Administrador1', 'master');
                break;
            /*
        case DatabaseFactory::TYPE_DB2:
            $resource = new Db2($credentials, $response);
            break;
        case DatabaseFactory::TYPE_MONGO:
            $resource = new Mongo($credentials, $response);
            break;
        case DatabaseFactory::TYPE_MONGOCLIENT:
            $resource = new MongoClient($credentials, $response);
            break;
        case DatabaseFactory::TYPE_ELASTICSEARCH:
            $resource = new Elasticsearch($credentials, $response);
            break;
        case DatabaseFactory::TYPE_MSSQL:
            $resource = new Mssql($credentials, $response);
            break;
        case DatabaseFactory::TYPE_MYSQL:
            $resource = new Mysql($credentials, $response);
            break;
        case DatabaseFactory::TYPE_MARIADB:
            $resource = new MariaDb($credentials, $response);
            break;
        case DatabaseFactory::TYPE_MYSQLE:
            $resource = new Mysqle($credentials, $response);
            $resource->execute("SET NAMES 'utf8'");
            break;
        case DatabaseFactory::TYPE_MSQL:
            $resource = new Msql($credentials, $response);
            break;
            */
        }
        return DatabaseFactory::init($credentials, $connection_type, RDBMS::RESPONSE_OBJECT);
    }
}
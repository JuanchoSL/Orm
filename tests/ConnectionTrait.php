<?php

namespace JuanchoSL\Orm\Tests;

use JuanchoSL\Logger\Logger;
use JuanchoSL\Orm\engine\DbCredentials;
use JuanchoSL\Orm\engine\Drivers\Mysqli;
use JuanchoSL\Orm\engine\Drivers\Odbc;
use JuanchoSL\Orm\engine\Drivers\Oracle;
use JuanchoSL\Orm\engine\Drivers\Postgres;
use JuanchoSL\Orm\Engine\Drivers\RDBMS;
use JuanchoSL\Orm\engine\Drivers\Sqlite;
use JuanchoSL\Orm\engine\Drivers\Sqlserver;
use JuanchoSL\Orm\engine\Engines;

trait ConnectionTrait
{
    public function getConnection(Engines $connection_type)
    {
        switch ($connection_type) {
            case Engines::TYPE_MYSQLI:
                $credentials = new DbCredentials(getenv('MYSQL_HOST'), getenv('MYSQL_USERNAME'), getenv('MYSQL_PASSWORD'), getenv('MYSQL_DATABASE'));
                $resource = new Mysqli($credentials, RDBMS::RESPONSE_OBJECT);
                break;

            case Engines::TYPE_SQLITE:
                $path = dirname(__DIR__, 1) . DIRECTORY_SEPARATOR . 'var';
                $credentials = new DbCredentials($path, '', '', 'test.db');
                $resource = new Sqlite($credentials, RDBMS::RESPONSE_OBJECT);
                break;

            case Engines::TYPE_POSTGRE:
                $credentials = new DbCredentials(getenv('POSTGRES_HOST'), getenv('POSTGRES_USERNAME'), getenv('POSTGRES_PASSWORD'), getenv('POSTGRES_DATABASE'));
                $resource = new Postgres($credentials, RDBMS::RESPONSE_OBJECT);
                break;

            case Engines::TYPE_SQLSRV:
                $credentials = new DbCredentials(getenv('SQLSRV_HOST'), getenv('SQLSRV_USERNAME'), getenv('SQLSRV_PASSWORD'), getenv('SQLSRV_DATABASE'));
                $resource = new Sqlserver($credentials, RDBMS::RESPONSE_OBJECT);
                break;

            case Engines::TYPE_ORACLE:
                $credentials = new DbCredentials(getenv('ORACLE_HOST'), getenv('ORACLE_USERNAME'), getenv('ORACLE_PASSWORD'), getenv('ORACLE_DATABASE'));
                $resource = new Oracle($credentials, RDBMS::RESPONSE_OBJECT);
                break;

            case Engines::TYPE_ODBC:
                $credentials = new DbCredentials(getenv('SQLSRV_HOST'), getenv('SQLSRV_USERNAME'), getenv('SQLSRV_PASSWORD'), getenv('SQLSRV_DATABASE'));
                $resource = new Odbc($credentials, RDBMS::RESPONSE_OBJECT);
                break;
        }
        $resource->setLogger(new Logger(dirname(__DIR__, 1) . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . 'database.log'));

        return $resource;
    }
}
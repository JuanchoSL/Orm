<?php

namespace JuanchoSL\Orm\Tests;

use JuanchoSL\Logger\Logger;
use JuanchoSL\Orm\DatabaseFactory;
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
                $credentials = new DbCredentials('localhost', 'test', 'test', 'test');
                $resource = new Mysqli($credentials, RDBMS::RESPONSE_OBJECT);
                break;

            case Engines::TYPE_SQLITE:
                $path = dirname(__DIR__, 1) . DIRECTORY_SEPARATOR . 'var';
                $credentials = new DbCredentials($path, '', '', 'test.db');
                $resource = new Sqlite($credentials, RDBMS::RESPONSE_OBJECT);
                break;

            case Engines::TYPE_POSTGRE:
                $credentials = new DbCredentials('localhost', 'root', 'root', 'test');
                $resource = new Postgres($credentials, RDBMS::RESPONSE_OBJECT);
                break;

            case Engines::TYPE_SQLSRV:
                $credentials = new DbCredentials('localhost', 'sa', 'Administrador1', 'master');
                $resource = new Sqlserver($credentials, RDBMS::RESPONSE_OBJECT);
                break;

            case Engines::TYPE_ORACLE:
                $credentials = new DbCredentials('localhost', 'SYS', 'oracle', 'SYSTEM');
                $resource = new Oracle($credentials, RDBMS::RESPONSE_OBJECT);
                break;

            case Engines::TYPE_ODBC:
                $credentials = new DbCredentials('localhost', 'sa', 'Administrador1', 'master');
                $resource = new Odbc($credentials, RDBMS::RESPONSE_OBJECT);
                break;
        }
        $resource->setLogger(new Logger(dirname(__DIR__, 1) . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . 'database.log'));

        return $resource;
    }
}
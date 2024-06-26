<?php

namespace JuanchoSL\Orm\Tests;

use JuanchoSL\Logger\Composers\PlainText;
use JuanchoSL\Logger\Handlers\FileHandler;
use JuanchoSL\Logger\Logger;
use JuanchoSL\Logger\Repositories\FileRepository;
use JuanchoSL\Orm\Datamodel\Model;
use JuanchoSL\Orm\engine\DbCredentials;
use JuanchoSL\Orm\engine\Drivers\Db2;
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
    private static array $connections = [];

    private static bool $git_mode = true;

    public static function setConnection(Engines $connection_type)
    {
        Model::setConnection(static::getConnection($connection_type));
    }

    public static function getConnection(Engines $connection_type)
    {
        if (array_key_exists($connection_type->value, self::$connections)) {
            return self::$connections[$connection_type->value];
        }

        if (!static::$git_mode) {
            $logger = new Logger((new FileRepository(getenv('LOG_FILEPATH')))->setComposer((new PlainText)->setTimeFormat(getenv('LOG_TIMEFORMAT'))));
            $logger->log('debug', "Creating {type}", ['function' => __FUNCTION__, 'memory' => memory_get_usage(), 'type' => $connection_type->value]);
        }
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

            case Engines::TYPE_DB2:
                $credentials = new DbCredentials(getenv('DB2_HOST'), getenv('DB2_USERNAME'), getenv('DB2_PASSWORD'), getenv('DB2_DATABASE'));
                $resource = new Db2($credentials, RDBMS::RESPONSE_OBJECT);
                break;
        }
        $resource->setLogger($logger, true);
        return self::$connections[$connection_type->value] = $resource;
        return $resource;
    }


    public function providerData(): array
    {
        if (static::$git_mode) {
            return ['Sqlite' => [self::getConnection(Engines::TYPE_SQLITE)]];
        }

        return [
            'Sqlite' => [self::getConnection(Engines::TYPE_SQLITE)],
            'Mysql' => [self::getConnection(Engines::TYPE_MYSQLI)],
            'Oracle' => [self::getConnection(Engines::TYPE_ORACLE)],
            'Postgres' => [self::getConnection(Engines::TYPE_POSTGRE)],
            'Sqlserver' => [self::getConnection(Engines::TYPE_SQLSRV)]
        ];
    }
}
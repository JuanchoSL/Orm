<?php

declare(strict_types=1);

namespace JuanchoSL\Orm\Tests;

use JuanchoSL\Logger\Composers\PlainText;
use JuanchoSL\Logger\Logger;
use JuanchoSL\Logger\Repositories\FileRepository;
use JuanchoSL\Orm\Engine\DbCredentials;
use JuanchoSL\Orm\Engine\Engines;
use JuanchoSL\Orm\Factory;

trait ConnectionTrait
{
    private static array $connections = [];

    private static bool $git_mode = true;

    public static function getConnection(Engines $connection_type)
    {
        if (array_key_exists($connection_type->value, self::$connections)) {
            return self::$connections[$connection_type->value];
        }

        switch ($connection_type) {
            case Engines::TYPE_MYSQLI:
                $credentials = new DbCredentials(getenv('MYSQL_HOST'), getenv('MYSQL_USERNAME'), getenv('MYSQL_PASSWORD'), getenv('MYSQL_DATABASE'));
                break;

            case Engines::TYPE_SQLITE:
                $path = dirname(__DIR__, 1) . DIRECTORY_SEPARATOR . 'var';
                $credentials = new DbCredentials($path, '', '', 'test.db');
                break;

            case Engines::TYPE_POSTGRE:
                $credentials = new DbCredentials(getenv('POSTGRES_HOST'), getenv('POSTGRES_USERNAME'), getenv('POSTGRES_PASSWORD'), getenv('POSTGRES_DATABASE'));
                break;

            case Engines::TYPE_SQLSRV:
                $credentials = new DbCredentials(getenv('SQLSRV_HOST'), getenv('SQLSRV_USERNAME'), getenv('SQLSRV_PASSWORD'), getenv('SQLSRV_DATABASE'));
                break;

            case Engines::TYPE_ORACLE:
                $credentials = new DbCredentials(getenv('ORACLE_HOST'), getenv('ORACLE_USERNAME'), getenv('ORACLE_PASSWORD'), getenv('ORACLE_DATABASE'));
                break;

            case Engines::TYPE_ODBC:
                $credentials = new DbCredentials(getenv('SQLSRV_HOST'), getenv('SQLSRV_USERNAME'), getenv('SQLSRV_PASSWORD'), getenv('SQLSRV_DATABASE'));
                break;

            case Engines::TYPE_DB2:
                $credentials = new DbCredentials(getenv('DB2_HOST'), getenv('DB2_USERNAME'), getenv('DB2_PASSWORD'), getenv('DB2_DATABASE'));
                break;
        }
        $resource = Factory::connection($credentials, $connection_type);

        if (!static::$git_mode) {
            $logger = new Logger((new FileRepository(getenv('LOG_FILEPATH')))->setComposer((new PlainText)->setTimeFormat(getenv('LOG_TIMEFORMAT'))));
            $logger->log('debug', "Creating {type}", ['function' => __FUNCTION__, 'memory' => memory_get_usage(), 'type' => $connection_type->value]);
            $resource->setLogger($logger);
            $resource->setDebug(true);
        }
        return self::$connections[$connection_type->value] = $resource;
    }


    public function providerData(): array
    {
        if (static::$git_mode) {
            return ['Sqlite' => [self::getConnection(Engines::TYPE_ODBC)]];
        }

        return [
            'Sqlite' => [self::getConnection(Engines::TYPE_SQLITE)],
            'Mysql' => [self::getConnection(Engines::TYPE_MYSQLI)],
            'Oracle' => [self::getConnection(Engines::TYPE_ORACLE)],
            'Postgres' => [self::getConnection(Engines::TYPE_POSTGRE)],
            'Sqlserver' => [self::getConnection(Engines::TYPE_SQLSRV)],
            'Db2' => [self::getConnection(Engines::TYPE_DB2)]
        ];
    }
}
<?php

declare(strict_types=1);

namespace JuanchoSL\Orm\Tests;

use JuanchoSL\Logger\Composers\PlainText;
use JuanchoSL\Logger\Logger;
use JuanchoSL\Logger\Repositories\FileRepository;
use JuanchoSL\Orm\Engine\DbCredentials;
use JuanchoSL\Orm\Engine\Enums\EngineEnums;
use JuanchoSL\Orm\Engine\Factory;
use Psr\Log\LoggerInterface;

trait ConnectionTrait
{
    protected static array $connections = [];

    private static bool $git_mode = true;

    public static function getConnection(EngineEnums $connection_type)
    {
        if (!array_key_exists($connection_type->value, static::$connections)) {

            switch ($connection_type) {
                case EngineEnums::TYPE_MYSQLI:
                    $credentials = new DbCredentials(getenv('MYSQL_HOST'), getenv('MYSQL_USERNAME'), getenv('MYSQL_PASSWORD'), getenv('MYSQL_DATABASE'));
                    break;

                case EngineEnums::TYPE_SQLITE:
                    $path = dirname(__DIR__, 1) . DIRECTORY_SEPARATOR . 'var';
                    $credentials = new DbCredentials($path, '', '', 'test.db');
                    break;

                case EngineEnums::TYPE_POSTGRE:
                    $credentials = new DbCredentials(getenv('POSTGRES_HOST'), getenv('POSTGRES_USERNAME'), getenv('POSTGRES_PASSWORD'), getenv('POSTGRES_DATABASE'));
                    break;

                case EngineEnums::TYPE_SQLSRV:
                    $credentials = new DbCredentials(getenv('SQLSRV_HOST'), getenv('SQLSRV_USERNAME'), getenv('SQLSRV_PASSWORD'), getenv('SQLSRV_DATABASE'));
                    break;

                case EngineEnums::TYPE_ORACLE:
                    $credentials = new DbCredentials(getenv('ORACLE_HOST'), getenv('ORACLE_USERNAME'), getenv('ORACLE_PASSWORD'), getenv('ORACLE_DATABASE'));
                    break;

                case EngineEnums::TYPE_ODBC:
                    switch (5) {
                        case 1:
                            //$dsn = "DRIVER={IBM DB2 ODBC DRIVER - DB2COPY1};SERVER=" . getenv('DB2_HOST') . ";";
                            $dsn = "docker";
                            $credentials = new DbCredentials($dsn, getenv('DB2_USERNAME'), getenv('DB2_PASSWORD'), '');
                            break;
                        case 2:
                            $dsn = "mysql";
                            $credentials = new DbCredentials($dsn, getenv('MYSQL_USERNAME'), getenv('MYSQL_PASSWORD'), '');
                            break;
                        case 3:
                            $dsn = "DRIVER={SQL Server};SERVER=" . getenv('SQLSRV_HOST') . ";";
                            $credentials = new DbCredentials($dsn, getenv('SQLSRV_USERNAME'), getenv('SQLSRV_PASSWORD'), getenv('SQLSRV_DATABASE'));
                            break;
                        case 4:
                            $dsn = "sqlserver-usuario";
                            $credentials = new DbCredentials($dsn, getenv('SQLSRV_USERNAME'), getenv('SQLSRV_PASSWORD'),null);
                            break;
                        case 5:
                            $dsn = "excel";
                            $credentials = new DbCredentials($dsn, '', '',null);
                            break;
                    }
                    break;
                case EngineEnums::TYPE_DB2:
                    $credentials = new DbCredentials(getenv('DB2_HOST'), getenv('DB2_USERNAME'), getenv('DB2_PASSWORD'), getenv('DB2_DATABASE'));
                    break;
            }
            $resource = Factory::connection($credentials, $connection_type);

            if (!static::$git_mode) {
                $logger = static::getLogger();
                $logger->log('debug', "Creating {type}", ['function' => __FUNCTION__, 'memory' => memory_get_usage(), 'type' => $connection_type->value]);
                $resource->setLogger($logger);
                //$resource->setDebug(true);
            }
            static::$connections[$connection_type->value] = $resource;
        }
        return static::$connections[$connection_type->value];
    }

    public static function getLogger(): LoggerInterface
    {
        static $logger;
        if (empty($logger)) {
            $logger = new Logger((new FileRepository(getenv('LOG_FILEPATH')))->setComposer((new PlainText)->setTimeFormat(getenv('LOG_TIMEFORMAT'))));
        }
        return $logger;
    }

    public function providerData(): array
    {
        if (static::$git_mode) {
            return ['Sqlite' => [self::getConnection(EngineEnums::TYPE_SQLITE)]];
            return ['ODBC' => [self::getConnection(EngineEnums::TYPE_ODBC)]];
        }

        return [
            'Sqlite' => [self::getConnection(EngineEnums::TYPE_SQLITE)],
            'Mysql' => [self::getConnection(EngineEnums::TYPE_MYSQLI)],
            'Oracle' => [self::getConnection(EngineEnums::TYPE_ORACLE)],
            'Postgres' => [self::getConnection(EngineEnums::TYPE_POSTGRE)],
            'Sqlserver' => [self::getConnection(EngineEnums::TYPE_SQLSRV)],
            'Db2' => [self::getConnection(EngineEnums::TYPE_DB2)]
        ];
    }
}
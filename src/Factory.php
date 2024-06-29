<?php

declare(strict_types=1);

namespace JuanchoSL\Orm;

use JuanchoSL\Exceptions\ServiceUnavailableException;
use JuanchoSL\Logger\Contracts\LogComposerInterface;
use JuanchoSL\Logger\Contracts\LogRepositoryInterface;
use JuanchoSL\Orm\engine\Drivers\Db2;
use Psr\Log\LoggerInterface;
use JuanchoSL\Logger\Logger;
use JuanchoSL\Logger\Composers\PlainText;
use JuanchoSL\Logger\Repositories\FileRepository;
use JuanchoSL\Orm\Datamodel\Model;
use JuanchoSL\Orm\engine\DbCredentials;
use JuanchoSL\Orm\engine\Drivers\DbInterface;
use JuanchoSL\Orm\engine\Drivers\Mysqli;
use JuanchoSL\Orm\engine\Drivers\Sqlite;
use JuanchoSL\Orm\engine\Drivers\Sqlserver;
use JuanchoSL\Orm\engine\Drivers\Postgres;
use JuanchoSL\Orm\engine\Drivers\Oracle;
use JuanchoSL\Orm\engine\Drivers\Odbc;
use JuanchoSL\Orm\engine\Engines;
use Psr\SimpleCache\CacheInterface;
use JuanchoSL\SimpleCache\Repositories\SessionCache;

class Factory
{

    public static function init(DbCredentials $credentials, Engines $type): DbInterface
    {
        $connection = static::connection($credentials, $type);
        $connection->setLogger(static::logger());
        Model::setConnection($connection);
        return $connection;
    }

    public static function connection(DbCredentials $credentials, Engines $type): DbInterface
    {
        switch ($type) {
            case Engines::TYPE_MYSQLI:
                $resource = new Mysqli($credentials);
                break;
            case Engines::TYPE_SQLITE:
                $resource = new Sqlite($credentials);
                break;
            case Engines::TYPE_POSTGRE:
                $resource = new Postgres($credentials);
                break;
            case Engines::TYPE_SQLSRV:
                $resource = new Sqlserver($credentials);
                break;
            case Engines::TYPE_ORACLE:
                $resource = new Oracle($credentials);
                break;
            case Engines::TYPE_ODBC:
                $resource = new Odbc($credentials);
                break;
            case Engines::TYPE_DB2:
                $resource = new Db2($credentials);
                break;
            default:
                throw new ServiceUnavailableException("The service type {$type->value} is not available");
            /*
            case Engines::TYPE_MONGO:
                $resource = new Mongo($credentials);
                break;
            case Engines::TYPE_MONGOCLIENT:
                $resource = new MongoClient($credentials);
                break;
            case Engines::TYPE_ELASTICSEARCH:
                $resource = new Elasticsearch($credentials);
                break;
            */
        }
        /*
        DBConnection::setConnection($resource);
        DBConnection::setCache(new SessionCache('orm'.$type->value));
        */
        return $resource;
    }

    public static function cache(): CacheInterface
    {
        return new SessionCache('OrmCache');
    }
    public static function logger(): LoggerInterface
    {
        return new Logger(static::logRepository());
    }
    public static function logRepository(): LogRepositoryInterface
    {
        return (new FileRepository(dirname(__DIR__, 1) . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . 'database.log'))->setComposer(static::logComposer());
    }
    public static function logComposer(): LogComposerInterface
    {
        return new PlainText;
    }
}

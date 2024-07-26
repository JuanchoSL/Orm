<?php

declare(strict_types=1);

namespace JuanchoSL\Orm\Engine;

use JuanchoSL\Exceptions\ServiceUnavailableException;
use JuanchoSL\Orm\Engine\Drivers\Db2;
use JuanchoSL\Orm\Engine\Drivers\DbInterface;
use JuanchoSL\Orm\Engine\Drivers\Mysqli;
use JuanchoSL\Orm\Engine\Drivers\Sqlite;
use JuanchoSL\Orm\Engine\Drivers\Sqlserver;
use JuanchoSL\Orm\Engine\Drivers\Postgres;
use JuanchoSL\Orm\Engine\Drivers\Oracle;
use JuanchoSL\Orm\Engine\Drivers\Odbc;
use JuanchoSL\Orm\Engine\Enums\EngineEnums;

class Factory
{

    public static function connection(DbCredentials $credentials, EngineEnums $type): DbInterface
    {
        switch ($type) {
            case EngineEnums::TYPE_MYSQLI:
                $resource = new Mysqli($credentials);
                break;
            case EngineEnums::TYPE_SQLITE:
                $resource = new Sqlite($credentials);
                break;
            case EngineEnums::TYPE_POSTGRE:
                $resource = new Postgres($credentials);
                break;
            case EngineEnums::TYPE_SQLSRV:
                $resource = new Sqlserver($credentials);
                break;
            case EngineEnums::TYPE_ORACLE:
                $resource = new Oracle($credentials);
                break;
            case EngineEnums::TYPE_ODBC:
                $resource = new Odbc($credentials);
                break;
            case EngineEnums::TYPE_DB2:
                $resource = new Db2($credentials);
                break;
            default:
                throw new ServiceUnavailableException("The service type {$type->value} is not available");
            /*
            case EngineEnums::TYPE_MONGO:
                $resource = new Mongo($credentials);
                break;
            case EngineEnums::TYPE_MONGOCLIENT:
                $resource = new MongoClient($credentials);
                break;
            case EngineEnums::TYPE_ELASTICSEARCH:
                $resource = new Elasticsearch($credentials);
                break;
            */
        }
        return $resource;
    }
}

<?php

declare(strict_types=1);

namespace JuanchoSL\Orm\Datamodel\Traits;

use JuanchoSL\Orm\Datamodel\DataModelInterface;
use JuanchoSL\Orm\Datamodel\QueryExecuter;

trait AutoQueryTrait
{

    public static function __callStatic($method, $args)
    {
        $instance = self::getInstance();
        $query = new QueryExecuter(self::$conn[$instance->connection_name], $instance);
        $query = $query->$method($args);
        return $query;
    }

    public static function where(array ...$where): QueryExecuter
    {
        $instance = self::getInstance();
        if ($instance->lazyLoad) {
            $distinct = array();
            $keys = $instance->getConnection()->keys($instance->getTableName());
            foreach ($keys as $key) {
                $distinct[] = "{$instance->getTableName()}.{$key}";
            }
            $fields = "DISTINCT " . implode(",", $distinct);
        } else {
            $fields = $instance->getTableName() . '.*';
        }
        $response = static::select($fields)->from($instance->getTableName());
        if (!empty($where)) {
            $response = call_user_func_array([$response, 'where'], $where);
        }
        return $response;
    }

    public static function findByPk($id): DataModelInterface
    {
        $instance = self::getInstance();
        $instance->identifier = $id;
        if ($instance->lazyLoad === true) {
            return $instance;
        } else {
            return $instance->load($id);
        }
    }
}
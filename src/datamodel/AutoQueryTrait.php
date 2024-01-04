<?php

namespace JuanchoSL\Orm\datamodel;

use JuanchoSL\Orm\querybuilder\QueryExecuter;


trait AutoQueryTrait
{

    public static function __callStatic($method, $args)
    {
        $instance = self::getInstance();
        $query = new QueryExecuter(self::$conn[$instance->connection_name], $instance);
        $query = $query->from($instance->getTableName())->$method($args);
        return $query;
    }

    public static function all()
    {
        return self::where()->get();
    }

    public static function where(array ...$where)
    {
        $instance = (isset($this)) ? $this : self::getInstance();
        if ($instance->lazyLoad) {
            $distinct = array();
            $keys = $instance->keys();
            foreach ($keys as $key) {
                $distinct[] = "{$instance->getTableName()}.{$key}";
            }
            $fields = "DISTINCT " . implode(",", $distinct);
        } else {
            $fields = '*';
        }
        $response = self::select($fields);
        if (!empty($where)) {
            $response = call_user_func_array([$response, 'where'], $where);
        }
        return $response;
    }

    public static function make(iterable $values)
    {
        $instance = self::getInstance();
        return $instance->fill($values);
    }

    public static function findByPk($id)
    {
        $instance = self::getInstance();
        $instance->identifier = $id;
        if ($instance->lazyLoad === true) {
            return $instance;
        } else {
            return $instance->load($id);
        }
    }

    public static function findOne(array $where = array(), array $inner = array())
    {
        return self::select()->where($where)->join($inner)->limit(1)->get()->first();
    }
}
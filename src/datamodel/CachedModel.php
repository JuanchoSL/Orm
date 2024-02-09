<?php

namespace JuanchoSL\Orm\datamodel;
use Psr\SimpleCache\CacheInterface;

abstract class CachedModel extends Model
{
    
    static $cache = [];

    public static function setCache(CacheInterface $cache, string $conection_name = 'default')
    {
        if (array_key_exists($conection_name, self::$conn)) {
            self::$cache[$conection_name] = $cache;
            return true;
        }
        return false;
    }

    public function save()
    {
        $result = parent::save();
        if ($result && array_key_exists($this->connection_name, self::$cache)) {
            self::$cache[$this->connection_name]->set($this->createCacheKey(), $this->values, 100);
        }
        return $result;
    }

    public function delete()
    {
        if (array_key_exists($this->connection_name, self::$cache)) {
            self::$cache[$this->connection_name]->delete($this->createCacheKey());
        }
        return parent::delete();
    }

    protected function load($id)
    {
        if (array_key_exists($this->connection_name, self::$cache)) {
            $element = self::$cache[$this->connection_name]->get($this->createCacheKey());
        }
        if (empty($element)) {
            $element = parent::load($id);
        } else {
            $this->fill((array) $element);
        }
        if (array_key_exists($this->connection_name, self::$cache)) {
            self::$cache[$this->connection_name]->set($this->createCacheKey(), $element, 100);
        }
    }

    protected function createCacheKey()
    {
        return md5($this->getTableName() . $this->getPrimaryKeyName() . $this->identifier);
    }
}
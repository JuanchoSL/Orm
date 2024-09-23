<?php

declare(strict_types=1);

namespace JuanchoSL\Orm\Datamodel;

use Psr\SimpleCache\CacheInterface;

abstract class CachedModel extends Model
{

    protected $lazyLoad = true;
    protected $ttl = 120;
    static $cache = [];

    public static function setCache(CacheInterface $cache, string $conection_name = 'default')
    {
        if (array_key_exists($conection_name, self::$conn)) {
            self::$cache[$conection_name] = $cache;
            return true;
        }
        return false;
    }

    public function save(): bool
    {
        $result = parent::save();
        if ($result && array_key_exists($this->connection_name, self::$cache)) {
            self::$cache[$this->connection_name]->set($this->createCacheKey(), json_decode(json_encode($this->values), true), $this->ttl);
        }
        return $result;
    }

    public function delete(): bool
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
            self::$cache[$this->connection_name]->set($this->createCacheKey(), $element, $this->ttl);
        }
        return $element;
    }

    protected function createCacheKey()
    {
        return md5($this->getTableName() . $this->getPrimaryKeyName() . $this->identifier);
    }
}
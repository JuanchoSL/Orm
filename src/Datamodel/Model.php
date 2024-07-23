<?php

declare(strict_types=1);

namespace JuanchoSL\Orm\Datamodel;

use JuanchoSL\Exceptions\UnprocessableEntityException;
use JuanchoSL\Orm\Datamodel\Traits\AutoCrudTrait;
use JuanchoSL\Orm\Datamodel\Traits\AutoQueryTrait;
use JuanchoSL\Orm\Datamodel\Traits\InstantiatorTrait;
use JuanchoSL\Orm\Datamodel\Traits\RelationsTrait;
use JuanchoSL\Orm\engine\Drivers\DbInterface;

/**
 * Description of Model
 *
 * @author Juancho
 */
abstract class Model implements \JsonSerializable, DataModelInterface
{
    use RelationsTrait, AutoQueryTrait, InstantiatorTrait, AutoCrudTrait;
    protected $identifier = 0;

    protected $loaded = false;

    protected $lazyLoad = false;

    protected $table = null;

    static $conn = [];

    protected $connection_name = 'default';

    public static function setConnection(DbInterface $connection, string $conection_name = 'default'): DbInterface
    {
        return self::$conn[$conection_name] = $connection;
    }

    protected function getConnection(): DbInterface
    {
        return static::$conn[$this->connection_name];
    }

    protected function adapterIdentifier($id)
    {
        /*if (is_string($id)) {
            switch (strtolower(get_class(self::$conn))) {
                case strtolower(\remote\database\Mongo::class):
                    $id = new \MongoDB\BSON\ObjectId($id);
                    break;

                case strtolower(\remote\database\MongoClient::class):
                    $id = new \MongoId($id);
                    break;
            }
        }*/
        return $id;
    }
    public function getPrimaryKeyValue():mixed
    {
        $pk = $this->getPrimaryKeyName();
        if ($this->values->has($pk)) {
            return $this->values->get($pk);
        } elseif (!empty ($this->identifier)) {
            return $this->identifier;
        }
        throw new UnprocessableEntityException($pk);
    }

    public function getPrimaryKeyName():string
    {
        $keys = $this->getConnection()->keys($this->getTableName());
        return (count($keys) > 0) ? (string) $keys[0] : ((true /*in_array(strtolower(get_class(self::$conn)), [strtolower(\remote\database\Mongo::class), strtolower(\remote\database\MongoClient::class)])*/) ? 'id' : '_id');
    }

    public function getTableName():string
    {
        return $this->table ?? $this->table = strtolower(substr(get_called_class(), strrpos(get_called_class(), '\\') + 1));
    }

    public function jsonSerialize(): mixed
    {
        if (!$this->loaded && !empty($this->identifier)) {
            $this->load($this->identifier);
        }
        $response = [];
        $columns = $this->getConnection()->columns($this->getTableName());
        if (!empty ($columns)) {
            foreach ($columns as $column) {
                if ($this->values->has($column)) {
                    $response[$column] = $this->values->get($column);
                }
            }
        }
        return $response;
    }
}

<?php

namespace JuanchoSL\Orm\datamodel;

use JuanchoSL\Exceptions\UnprocessableEntityException;
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

    public function __call($method, $parameters)
    {
        self::$conn[$this->connection_name]->setTable($this->getTableName());
        return call_user_func_array(array(self::$conn[$this->connection_name], $method), $parameters);
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
    public function getPrimaryKeyValue()
    {
        $pk = $this->getPrimaryKeyName();
        if (isset($this->values[$pk])) {
            return $this->values[$pk];
        } elseif (!empty($this->identifier)) {
            return $this->identifier;
        }
        throw new UnprocessableEntityException($pk);
    }

    public function getPrimaryKeyName()
    {
        $keys = $this->keys();
        return (count($keys) > 0) ? (string) $keys[0] : ((true /*in_array(strtolower(get_class(self::$conn)), [strtolower(\remote\database\Mongo::class), strtolower(\remote\database\MongoClient::class)])*/) ? 'id' : '_id');
    }

    public function getTableName()
    {
        return $this->table ?? $this->table = strtolower(substr(get_called_class(), strrpos(get_called_class(), '\\') + 1));
    }

    public function jsonSerialize(): mixed
    {
        if (!$this->loaded) {
            $this->load($this->identifier);
        }
        $response = [];
        $columns = $this->columns();
        if (!empty($columns)) {
            foreach ($columns as $column) {
                if (array_key_exists($column, $this->values)) {
                    $response[$column] = $this->values[$column];
                }
            }
        }
        return $response;
    }
}

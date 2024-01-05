<?php

namespace JuanchoSL\Orm\datamodel;
use JuanchoSL\Exceptions\UnprocessableEntityException;

/**
 * Description of Model
 *
 * @author Juancho
 */
abstract class Model extends DBConnection implements \JsonSerializable, DataModelInterface
{
    use RelationsTrait, AutoQueryTrait, InstantiatorTrait, AutoCrudTrait;
    private $identifier = 0;

    private $loaded = false;

    protected $lazyLoad = true;

    protected $table = null;



    public function getPrimaryKeyValue()
    {
        $pk = $this->getPrimaryKeyName();
        if (isset($this->values[$pk])) {
            return $this->values[$pk];
        }elseif (!empty($this->identifier)) {
            return $this->identifier;
        }
        //print_r($this);exit;
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
                if (isset($this->values[$column])) {
                    $response[$column] = $this->values[$column];
                }
            }
        }
        return $response;
    }
}

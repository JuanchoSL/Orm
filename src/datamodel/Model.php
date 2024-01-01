<?php

namespace JuanchoSL\Orm\datamodel;

/**
 * Description of Model
 *
 * @author Juancho
 */
abstract class Model extends DBConnection implements \JsonSerializable, DataModelInterface
{
    use RelationsTrait, AutoQueryTrait, InstantiatorTrait, AutoCrudTrait;
    protected $identifier = 0;

    private $loaded = false;

    private $lazyLoad = true;

    protected $table = null;

    protected $connection_name = 'default';



    public function getTableName()
    {
        return $this->table ?? $this->table = strtolower(substr(get_called_class(), strrpos(get_called_class(), '\\') + 1));
    }

    /*

    public static function find(array $where = array(), $order_by = null, $limit = false, $page = 0, array $inner = array())
    {
        $instance = (isset($this)) ? $this : self::getInstance();
        $keys = $instance->keys();

        //if (in_array(strtolower(get_class($instance)), [strtolower(__NAMESPACE__ . '\\' . \remote\database\Mongo::class), strtolower(__NAMESPACE__ . '\\' . \remote\database\MongoClient::class)])) {
        if (false) {
            $instance->select($where, $order_by, $page, $limit, [$instance->getPrimaryKeyName() => true]);
        } else {
            $distinct = array();
            foreach ($keys as $key) {
                $distinct[] = "{$instance->getTableName()}.{$key}";
            }
            //return self::select("DISTINCT " . implode(",", $distinct));
            $query = new QueryBuilder();
            $query->select(["DISTINCT " . implode(",", $distinct)])->from($instance->getTableName())->join($inner)->where($where)->orderBy($order_by)->limit($limit, $page);
            $cursor = $instance->execute($query);
        }
        $arr_results = $cursor->get();
        $collection = new Collection();
        foreach ($arr_results as $result) {
            $key = (string) $keys[0];
            $identifier = ($instance->getTypeReturn() == RDBMS::RESPONSE_OBJECT) ? (string) $result->{$key} : (string) $result[$key];
            $elemnt = self::getInstance()->findByPk($identifier);
            $collection->insert($elemnt);
        }
        unset($arr_results);
        $collection->rewind();
        return $collection;
    }
*/
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

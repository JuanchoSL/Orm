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

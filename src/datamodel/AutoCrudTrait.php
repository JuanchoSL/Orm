<?php

namespace JuanchoSL\Orm\datamodel;

use JuanchoSL\Exceptions\UnprocessableEntityException;
use JuanchoSL\Orm\engine\Relations\AbstractRelation;
use JuanchoSL\Orm\engine\Relations\BelongsToMany;
use JuanchoSL\Orm\engine\Relations\BelongsToOne;
use JuanchoSL\Orm\engine\Relations\OneToMany;
use JuanchoSL\Orm\engine\Relations\OneToOne;
use JuanchoSL\Orm\querybuilder\QueryBuilder;


trait AutoCrudTrait
{
    protected $values = [];
    public function delete()
    {
        return self::where([$this->getPrimaryKeyName(), $this->getPrimaryKeyValue()])->delete();
    }

    public function save()
    {
        $save = [];
        $columns = $this->columns();

        if (!empty($columns)) {
            foreach ($columns as $column) {
                if (isset($this->values[$column])) {
                    $save[$column] = $this->values[$column];
                }
            }
        } else {
            /*
            $columns = array_keys(get_object_vars($this));

            $reflect = new \ReflectionClass(DatabaseFactory::class);
            $attributes = $reflect->getProperties();
            $attributes = array_map(function ($e) {
                return $e->name;
            }, $attributes);
            $attributes = array_flip($attributes);

            $reflect = new \ReflectionClass($this->linkIdentifier);
            $attributes2 = $reflect->getProperties();
            $attributes2 = array_map(function ($e) {
                return $e->name;
            }, $attributes2);
            $attributes2 = array_flip($attributes2);

            $reflect = new \ReflectionClass(get_called_class());
            $attributes3 = $reflect->getProperties();
            $attributes3 = array_map(function ($e) {
                return $e->name;
            }, $attributes3);
            $attributes3 = array_flip($attributes3);

            $attributes = array_merge($attributes, $attributes2, $attributes3);
            foreach ($columns as $i => $col) {
                if (!array_key_exists($col, $attributes)) {
                    $save[$col] = $this->$col;
                }
            }
            */
        }
        try {
            $pk = $this->getPrimaryKeyName();
            $id = $this->getPrimaryKeyValue();
            unset($save[$pk]);
            return self::where([$pk, $id])->update($save);
        } catch (UnprocessableEntityException $ex) {
            $result = self::insert($save);
            if ($result) {
                $this->identifier = $this->$pk = $result;
                $this->loaded = true;
            }
            return $result;
        }
    }


    public function __set($param, $value)
    {
        if (in_array($param, $this->columns())) {
            $function = "set" . ucfirst(strtolower($param));
            if (method_exists($this, $function)) {
                $value = $this->$function($value);
            }
            $this->values[$param] = $value;
        }
    }

    public function __get($param)
    {

        if ($this->loaded === false) {
            $this->load($this->identifier);
        }
        if (method_exists($this, $param)) {
            $var = call_user_func([$this, $param]);
            if ($var instanceof AbstractRelation) {
                switch (get_class($var)) {
                    case OneToMany::class:
                        return $var->get();
                    case OneToOne::class:
                        return $var->first();
                    case BelongsToMany::class:
                        return $var->get();
                    case BelongsToOne::class:
                        return $var->first();
                }
            }
        } elseif (isset($this->values[strtolower($param)])) {
            $function = "get" . ucfirst(strtolower($param));
            if (method_exists($this, $function)) {
                return $this->$function();
            }
            return $this->values[strtolower($param)];
        }
        return null;
    }

    protected function load($id)
    {
        $pk = $this->getPrimaryKeyName();
        $id = $this->adapterIdentifier($id);
        $cursor = $this->execute(QueryBuilder::getInstance()->select()->from($this->getTableName())->where([$pk, $id])->limit(1));
        // if ($cursor->count() > 0) {
        $element = $cursor->next();
        if ($element) {
            foreach ($element as $name => $var) {
                if (is_bool($var)) {
                    $var = (bool) $var;
                } else if (is_double($var)) {
                    $var = (double) $var;
                } else if (is_float($var)) {
                    $var = (float) $var;
                } else if (is_bool($var) || is_int($var)) {
                    $var = (int) $var;
                } else if (is_string($var)) {
                    $encoding = mb_detect_encoding($var);
                    if ($encoding !== 'utf-8') {
                        $var = mb_convert_encoding($var, 'utf-8', $encoding);
                    }
                }
                $this->values[strtolower($name)] = $var;
            }
            $this->loaded = true;
        }
        $cursor->free();
        return $this;
        //}
        $cursor->free();
        return false;
    }

}
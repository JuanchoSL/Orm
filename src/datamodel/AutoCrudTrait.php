<?php

namespace JuanchoSL\Orm\datamodel;

use JuanchoSL\DataTransfer\Contracts\DataTransferInterface;
use JuanchoSL\Exceptions\NotFoundException;
use JuanchoSL\Exceptions\UnprocessableEntityException;
use JuanchoSL\Orm\engine\Relations\AbstractRelation;
use JuanchoSL\Orm\engine\Relations\BelongsToMany;
use JuanchoSL\Orm\engine\Relations\BelongsToOne;
use JuanchoSL\Orm\engine\Relations\OneToMany;
use JuanchoSL\Orm\engine\Relations\OneToOne;
use JuanchoSL\Orm\querybuilder\QueryBuilder;


trait AutoCrudTrait
{
    protected DataTransferInterface $values;
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
                if($this->values->has($column)){
                    $save[$column] = $this->values->get($column);
                }
/*
                if (isset($this->values[$column])) {
                    $save[$column] = $this->values[$column];
                }*/
            }
        }
        $pk = $this->getPrimaryKeyName();
        unset($save[$pk]);
        try {
            $id = $this->getPrimaryKeyValue();
            $result = self::where([$pk, $id])->update($save);
        } catch (UnprocessableEntityException $ex) {
            $result = self::insert($save);
            if ($result) {
                $this->identifier = $this->{$pk} = $result;
                $this->loaded = true;
            }
        }
        return $result;
    }


    public function __set($param, $value)
    {
        if (in_array($param, $this->columns())) {
            $function = "set" . ucfirst(strtolower($param));
            if (method_exists($this, $function)) {
                $value = $this->$function($value);
            }
            $this->values->set($param, $value);
            //$this->values[$param] = $value;
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
        } elseif ($this->values->has(strtolower($param))) {
        //} elseif (array_key_exists(strtolower($param), $this->values)) {
            $function = "get" . ucfirst(strtolower($param));
            if (method_exists($this, $function)) {
                return $this->$function();
            }
            return $this->values->get(strtolower($param));
            return $this->values[strtolower($param)];
        }
        return null;
    }

    protected function fill(iterable $element)
    {
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
            $this->values->set(strtolower($name), $var);
            //$this->values[strtolower($name)] = $var;
            if (empty($this->identifier) && $this->getPrimaryKeyName() == $name) {
                $this->identifier = $var;
            }
        }
        $this->loaded = true;
        return $this;
    }
    protected function load($id)
    {
        $pk = $this->getPrimaryKeyName();
        $id = $this->adapterIdentifier($id);
        $cursor = $this->execute(QueryBuilder::getInstance()->select()->from($this->getTableName())->where([$pk, $id])->limit(1));
        $element = $cursor->next();
        $cursor->free();
        if (!$element) {
            throw new NotFoundException("The element with {$pk}={$id} does not exists into {$this->getTableName()}");
        }
        if ($element) {
            $this->fill((array) $element);
        }
        return $element;
    }

}
<?php

declare(strict_types=1);

namespace JuanchoSL\Orm\Datamodel;

use JuanchoSL\DataTransfer\Contracts\DataTransferInterface;
use JuanchoSL\Exceptions\NotFoundException;
use JuanchoSL\Exceptions\UnprocessableEntityException;
use JuanchoSL\Orm\Datamodel\Relations\AbstractRelation;
use JuanchoSL\Orm\Datamodel\Relations\BelongsToMany;
use JuanchoSL\Orm\Datamodel\Relations\BelongsToOne;
use JuanchoSL\Orm\Datamodel\Relations\OneToMany;
use JuanchoSL\Orm\Datamodel\Relations\OneToOne;
use JuanchoSL\Orm\querybuilder\QueryBuilder;


trait AutoCrudTrait
{
    protected DataTransferInterface $values;
    public function delete(): bool
    {
        return static::where([$this->getPrimaryKeyName(), $this->getPrimaryKeyValue()])->delete()->count() > 0;
    }

    public function save(): bool
    {
        if (!$this->values->hasElements()) {
            return false;
        }
        $save = [];
        $columns = $this->getConnection()->columns($this->getTableName());

        if (!empty($columns)) {
            foreach ($columns as $column) {
                if ($this->values->has($column)) {
                    $save[$column] = $this->values->get($column);
                }
            }
        }
        $pk = $this->getPrimaryKeyName();
        unset($save[$pk]);
        try {
            $id = $this->getPrimaryKeyValue();
            $result = static::where([$pk, $id])->update($save)->count() == 1;
        } catch (UnprocessableEntityException $ex) {
            $result = static::where()->insert($save);
            if ($result->count() == 1) {
                $result = $result->__toString();
                $this->identifier = $this->{$pk} = $result;
                $this->loaded = true;
                $result = true;
            } else {
                $result = false;
            }
        }
        return $result;
    }

    public function __set($param, $value)
    {
        if (!$this->loaded && !empty($this->identifier)) {
            $this->load($this->identifier);
        }
        if (in_array($param, $this->getConnection()->columns($this->getTableName()))) {
            $function = "set" . str_replace(" ", "", ucwords(strtolower(str_replace("_", " ", $param))));
            if (method_exists($this, $function)) {
                $value = $this->$function($value);
            }
            $this->values->set($param, $value);
        }
    }

    public function __get($param)
    {
        if (!$this->loaded && !empty($this->identifier)) {
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
            $function = "get" . str_replace(" ", "", ucwords(strtolower(str_replace("_", " ", $param))));
            if (method_exists($this, $function)) {
                return $this->$function();
            }
            return $this->values->get(strtolower($param));
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
        $cursor = $this->getConnection()->execute(QueryBuilder::getInstance()->select()->from($this->getTableName())->where([$pk, $id])->limit(1));
        $element = $cursor->next();
        $cursor->free();
        if (!$element) {
            throw new NotFoundException("The element with {$pk}={$id} does not exists into {$this->getTableName()}");
        } else {
            $this->fill((array) $element);
        }
        return $element;
    }

}
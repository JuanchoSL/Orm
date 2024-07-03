<?php

declare(strict_types=1);

namespace JuanchoSL\Orm\Datamodel;

use JuanchoSL\DataTransfer\Contracts\DataTransferInterface;
use JuanchoSL\Exceptions\NotFoundException;
use JuanchoSL\Exceptions\UnprocessableEntityException;
use JuanchoSL\Orm\Querybuilder\QueryBuilder;


trait AutoCrudTrait
{
    protected DataTransferInterface $values;

    protected array $relations = [];

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
        $columns = $this->getConnection()->describe($this->getTableName());

        if (!empty($columns)) {
            foreach ($columns as $column => $description) {
                if ($this->values->has($column) && !$description->isKey()) {
                    $save[$column] = $this->values->get($column);
                }
            }
        }
        $pk = $this->getPrimaryKeyName();
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
            $return = $var->get();
            if (isset($return)) {
                $first = $return->first();
                if (isset($first, $this->relations[$this->getTableName()][$first->getTableName()])) {
                    $return = $return->first();
                }
            }
            return $return;
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
        $identifier = null;
        $this->loaded = true;
        foreach ($element as $name => $var) {
            $this->__set(strtolower($name), $var);
            if (empty($identifier) && $this->getPrimaryKeyName() == $name) {
                $identifier = $var;
            }
        }
        $this->identifier = $identifier;
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
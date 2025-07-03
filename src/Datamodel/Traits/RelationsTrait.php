<?php

declare(strict_types=1);

namespace JuanchoSL\Orm\Datamodel\Traits;

use JuanchoSL\Orm\Datamodel\DataModelInterface;

trait RelationsTrait
{

    protected function OneToMany(string|DataModelInterface $model, ?string $foreing_field = null, ?string $owner_field = null)
    {
        $model = is_string($model) ? new $model : $model;
        return $model::where([$this->createFieldNameChildren($this, $foreing_field), (string) $this->createFieldValue($owner_field)]);
    }

    protected function OneToOne(string|DataModelInterface $model, ?string $foreing_field = null, ?string $owner_field = null)
    {
        $model = is_string($model) ? new $model : $model;
        $this->relations[$this->getTableName()][$model->getTableName()] = 'first';
        return $model::where([$this->createFieldNameChildren($this, $foreing_field), (string) $this->createFieldValue($owner_field)])->limit(1);
    }

    protected function BelongsToOne(string|DataModelInterface $model, ?string $foreing_field = null, ?string $owner_field = null)
    {
        $model = is_string($model) ? new $model : $model;
        $this->relations[$this->getTableName()][$model->getTableName()] = 'first';
        return $model::where([$this->createFieldNameParent($model, $foreing_field), (string) $this->createFieldValue($this->createFieldNameChildren($model, $owner_field))])->limit(1);
    }

    protected function BelongsToMany(string|DataModelInterface $model, string|DataModelInterface $pivot, ?string $foreing_field = null, ?string $owner_field = null, ?string $pivot_foreing_field = null, ?string $pivot_owner_field = null)
    {
        $model = is_string($model) ? new $model : $model;
        $pivot = is_string($pivot) ? new $pivot : $pivot;

        $foreing_field = $this->createFieldNameParent($model, $foreing_field);
        $pivot_foreing_field = $this->createFieldNameChildren($model, $pivot_foreing_field);

        $pivot_owner_field = $this->createFieldNameChildren($this, $pivot_owner_field);
        $owner_value = (string) $this->createFieldValue($owner_field);

        $pivot_table = $pivot->getTableName();
        return $model::where([$pivot_table . "." . $pivot_owner_field, $owner_value])->join(["JOIN " . $pivot_table . " ON " . $pivot_table . "." . $pivot_foreing_field . "=" . $model->getTableName() . "." . $foreing_field]);
    }

    private function createFieldNameParent($model, $field_name = null)
    {
        return $field_name ?? $model->getPrimaryKeyName();
    }

    private function createFieldNameChildren($model, $field_name = null)
    {
        return $field_name ?? strtolower($model->getTableName()) . "_" . $model->getPrimaryKeyName();
    }

    private function createFieldValue($field_name = null)
    {
        $field_name ??= $this->createFieldNameParent($this, $field_name);
        return (string) $this->{$field_name};
    }
}
<?php

declare(strict_types=1);

namespace JuanchoSL\Orm\Datamodel;

use JuanchoSL\Orm\Datamodel\Relations\AbstractRelation;
use JuanchoSL\Orm\Datamodel\Relations\BelongsToMany;
use JuanchoSL\Orm\Datamodel\Relations\BelongsToOne;
use JuanchoSL\Orm\Datamodel\Relations\OneToMany;
use JuanchoSL\Orm\Datamodel\Relations\OneToOne;

trait RelationsTrait
{
    protected function OneToMany(DataModelInterface $model, string $foreing_field = null, string $owner_field = null): AbstractRelation
    {
        return new OneToMany($model, $this->createFieldNameChildren($this, $foreing_field), (string) $this->createFieldValue($owner_field));
    }

    protected function OneToOne(DataModelInterface $model, string $foreing_field = null, string $owner_field = null): AbstractRelation
    {
        return new OneToOne($model, $this->createFieldNameChildren($this, $foreing_field), (string) $this->createFieldValue($owner_field));
    }

    protected function BelongsToOne(DataModelInterface $model, string $foreing_field = null, string $owner_field = null): AbstractRelation
    {
        return new BelongsToOne($model, $this->createFieldNameParent($model, $foreing_field), (string) $this->createFieldValue($this->createFieldNameChildren($model, $owner_field)));
    }

    protected function BelongsToMany(DataModelInterface $model, DataModelInterface $pivot, string $foreing_field = null, string $owner_field = null, string $pivot_foreing_field = null, string $pivot_owner_field = null): AbstractRelation
    {
        return new BelongsToMany(
            $model,
            $this->createFieldNameParent($model, $foreing_field),
            $this->createFieldNameChildren($model, $pivot_foreing_field),
            $pivot,
            $this->createFieldNameChildren($this, $pivot_owner_field),
            (string) $this->createFieldValue($owner_field)
        );
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
        $field_name = $field_name ?? $this->createFieldNameParent($this, $field_name);
        return (string) $this->{$field_name};
    }
}
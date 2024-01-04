<?php

namespace JuanchoSL\Orm\datamodel;

use JuanchoSL\Orm\engine\Relations\AbstractRelation;
use JuanchoSL\Orm\engine\Relations\BelongsToMany;
use JuanchoSL\Orm\engine\Relations\BelongsToOne;
use JuanchoSL\Orm\engine\Relations\OneToMany;
use JuanchoSL\Orm\engine\Relations\OneToOne;

trait RelationsTrait
{
    protected function OneToMany(DataModelInterface $model, string $foreing_field = null, string $owner_field = null): AbstractRelation
    {
        $foreing_field = $foreing_field ?? strtolower($this->getTableName()) . "_" . $this->getPrimaryKeyName();
        $owner_value = $owner_field ? $this->{$owner_field} : $this->getPrimaryKeyValue();
        return new OneToMany($model, $foreing_field, $owner_value);
    }

    protected function OneToOne(DataModelInterface $model, string $foreing_field = null, string $owner_field = null): AbstractRelation
    {
        $foreing_field = $foreing_field ?? strtolower($this->getTableName()) . "_" . $this->getPrimaryKeyName();
        $owner_value = $owner_field ? $this->{$owner_field} : $this->getPrimaryKeyValue();
        return new OneToOne($model, $foreing_field, $owner_value);
    }

    protected function BelongsToOne(DataModelInterface $model, string $foreing_field = null, string $owner_field = null): AbstractRelation
    {
        $foreing_field = $foreing_field ?? $model->getPrimaryKeyName();
        $owner_field = $owner_field ?? strtolower($model->getTableName()) . "_" . $model->getPrimaryKeyName();
        return new BelongsToOne($model, $foreing_field, $this->{$owner_field});
    }

    protected function BelongsToMany(DataModelInterface $model, string $foreing_field = null, string $owner_field = null): AbstractRelation
    {
        return new BelongsToMany($model, 'id', 'id');
    }
}
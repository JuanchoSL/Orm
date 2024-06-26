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
        $foreing_field = $foreing_field ?? strtolower($this->getTableName()) . "_" . $this->getPrimaryKeyName();
        $owner_value = $owner_field ? $this->{$owner_field} : $this->getPrimaryKeyValue();
        return new OneToMany($model, $foreing_field, (string) $owner_value);
    }

    protected function OneToOne(DataModelInterface $model, string $foreing_field = null, string $owner_field = null): AbstractRelation
    {
        $foreing_field = $foreing_field ?? strtolower($this->getTableName()) . "_" . $this->getPrimaryKeyName();
        $owner_value = $owner_field ? $this->{$owner_field} : $this->getPrimaryKeyValue();
        return new OneToOne($model, $foreing_field, (string) $owner_value);
    }

    protected function BelongsToOne(DataModelInterface $model, string $foreing_field = null, string $owner_field = null): AbstractRelation
    {
        $foreing_field = $foreing_field ?? $model->getPrimaryKeyName();
        $owner_field = $owner_field ?? strtolower($model->getTableName()) . "_" . $model->getPrimaryKeyName();
        return new BelongsToOne($model, $foreing_field, (string) $this->{$owner_field});
    }

    protected function BelongsToMany(DataModelInterface $model, DataModelInterface $response, string $foreing_field = null, string $owner_field = null): AbstractRelation
    {
        return new BelongsToMany($model, 'id', 'id');
    }
}
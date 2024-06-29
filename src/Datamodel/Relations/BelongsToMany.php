<?php

declare(strict_types=1);

namespace JuanchoSL\Orm\Datamodel\Relations;

use JuanchoSL\Orm\Datamodel\DataModelInterface;

class BelongsToMany extends AbstractMultipleRetriever
{
    public function __construct(DataModelInterface $model, string $foreing_field, string $pivot_foreing_field, DataModelInterface $pivot, string $pivot_owner_field, string $owner_value)
    {
        $pivot_table = $pivot->getTableName();
        $this->relation = $model::where([$pivot_owner_field, $owner_value])->join("JOIN " . $pivot_table . " ON " . $pivot_table . "." . $pivot_foreing_field . "=" . $model->getTableName() . "." . $foreing_field);
    }

}
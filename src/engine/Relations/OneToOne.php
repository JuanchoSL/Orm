<?php

namespace JuanchoSL\Orm\engine\Relations;

use JuanchoSL\Orm\datamodel\DataModelInterface;

class OneToOne extends AbstractRelation
{
    public function __construct(DataModelInterface $model, string $foreign_key, string $id)
    {
        $this->relation = $model::where([$foreign_key, $id])->limit(1);
    }
}
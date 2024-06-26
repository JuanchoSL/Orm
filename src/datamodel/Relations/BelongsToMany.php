<?php

declare(strict_types=1);

namespace JuanchoSL\Orm\Datamodel\Relations;

use JuanchoSL\Orm\Datamodel\DataModelInterface;

class BelongsToMany extends AbstractMultipleRetriever
{
    public function __construct(DataModelInterface $model, string $foreign_key, string $id)
    {
        $this->relation = $model::where([$foreign_key, $id]);
    }

}
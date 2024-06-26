<?php

declare(strict_types=1);

namespace JuanchoSL\Orm\Datamodel\Relations;

use JuanchoSL\Orm\Datamodel\DataModelInterface;

abstract class AbstractSingleRetriever extends AbstractRelation
{
    public function first(): DataModelInterface
    {
        return $this->relation->first();
    }
    
    public function last(): DataModelInterface
    {
        return $this->relation->last();
    }
}
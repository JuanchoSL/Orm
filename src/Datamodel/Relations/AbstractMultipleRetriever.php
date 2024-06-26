<?php

declare(strict_types=1);

namespace JuanchoSL\Orm\Datamodel\Relations;

use JuanchoSL\Orm\Collection;

abstract class AbstractMultipleRetriever extends AbstractSingleRetriever
{
    public function get(): Collection
    {
        return $this->relation->get();
    }

}
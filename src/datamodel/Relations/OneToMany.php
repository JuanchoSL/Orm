<?php

declare(strict_types=1);

namespace JuanchoSL\Orm\Datamodel\Relations;

use JuanchoSL\Orm\Datamodel\DataModelInterface;

class OneToMany extends AbstractMultipleRetriever
{
    public function __construct(DataModelInterface $model, string $foreign_key, string $id)
    {
        $this->foreign_field = $foreign_key;
        $this->foreign_key = $id;
        $this->relation = $model::where([$foreign_key, $id]);
    }

    public function insert(array $values)
    {
        $values[$this->foreign_field] = $this->foreign_key;
        return $this->relation->insert($values);
    }
}
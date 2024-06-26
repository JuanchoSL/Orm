<?php

namespace JuanchoSL\Orm\Tests;

use JuanchoSL\Orm\Datamodel\Model;
use JuanchoSL\Orm\Datamodel\CachedModel;


class Other extends CachedModel
{

    public function test()
    {
        return $this->BelongsToOne(TestDb::getInstance());
    }
}
<?php

namespace JuanchoSL\Orm\Tests;

use JuanchoSL\Orm\datamodel\Model;
use JuanchoSL\Orm\datamodel\CachedModel;


class Other extends CachedModel
{

    public function test()
    {
        return $this->BelongsToOne(TestDb::getInstance());
    }
}
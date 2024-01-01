<?php

namespace JuanchoSL\Orm\Tests;

use JuanchoSL\Orm\datamodel\Model;


class Other extends Model
{

    public function test()
    {
        return $this->BelongsToOne(TestDb::getInstance());
    }
}
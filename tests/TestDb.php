<?php

namespace JuanchoSL\Orm\Tests;

use JuanchoSL\Orm\Datamodel\Model;
use JuanchoSL\Orm\Datamodel\CachedModel;


class TestDb extends CachedModel
{

    protected $table = 'test';

    public function other()
    {
        return $this->OneToMany(Other::getInstance());
    }
}
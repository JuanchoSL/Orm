<?php

namespace JuanchoSL\Orm\Tests;

use JuanchoSL\Orm\datamodel\Model;
use JuanchoSL\Orm\datamodel\CachedModel;


class TestDb extends CachedModel
{

    protected $table = 'test';

    public function other()
    {
        return $this->OneToMany(Other::getInstance());
    }
}
<?php

namespace JuanchoSL\Orm\Tests;

use JuanchoSL\Orm\datamodel\Model;


class TestDb extends Model
{

    protected $table = 'test';

    public function other()
    {
        return $this->OneToMany(Other::getInstance());
    }
}
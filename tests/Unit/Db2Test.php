<?php

namespace JuanchoSL\Orm\Tests\Unit;

use JuanchoSL\Orm\Engine\Drivers\Db2;
use JuanchoSL\Orm\engine\Drivers\RDBMS;
use JuanchoSL\Orm\engine\Engines;
use JuanchoSL\Orm\engine\DbCredentials;

class Db2Test extends AbstractUnit
{

    public function setUp(): void
    {
        $this->db_type = Engines::TYPE_DB2;
        parent::setUp();
        $credentials = new DbCredentials('localhost', 'db2inst1', 'password', 'test');
        $this->db = new Db2($credentials, RDBMS::RESPONSE_OBJECT);
    }

}
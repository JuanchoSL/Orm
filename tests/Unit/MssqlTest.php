<?php

namespace JuanchoSL\Orm\Tests\Unit;

use JuanchoSL\Orm\Engine\Drivers\Mssql;
use JuanchoSL\Orm\engine\Drivers\RDBMS;
use JuanchoSL\Orm\engine\Engines;
use JuanchoSL\Orm\engine\DbCredentials;

class MssqlTest extends AbstractUnitTest
{

    public function setUp(): void
    {
        $this->db_type = Engines::TYPE_MSSQL;
        parent::setUp();
        $credentials = new DbCredentials('localhost', 'sa', 'Administrador1', 'master');
        $this->db = new Mssql($credentials, RDBMS::RESPONSE_OBJECT);
    }

}
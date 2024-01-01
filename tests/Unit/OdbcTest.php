<?php

namespace JuanchoSL\Orm\Tests\Unit;

use JuanchoSL\Orm\engine\Engines;

class OdbcTest extends AbstractUnitTest
{
    protected $db_type = Engines::TYPE_ODBC;

    public function queryCreateTable()
    {
        return "CREATE TABLE test (
            id int IDENTITY(1,1) PRIMARY KEY,
            test varchar(16) NOT NULL,
            dato varchar(16) NOT NULL
        );";
    }
}
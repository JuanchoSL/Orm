<?php

namespace JuanchoSL\Orm\Tests\Functional;

use JuanchoSL\Orm\engine\Drivers\RDBMS;
use JuanchoSL\Orm\DatabaseFactory;
use JuanchoSL\Orm\engine\Engines;
use JuanchoSL\Orm\Tests\TestDb;
use JuanchoSL\Orm\engine\DbCredentials;

class SqliteTest extends AbstractFunctionalTest
{
    protected $db_type = Engines::TYPE_SQLITE;

}
<?php

namespace JuanchoSL\Orm\Tests\Functional;

use JuanchoSL\Orm\engine\Engines;

class SqliteTest extends AbstractFunctionalTest
{
    protected Engines $db_type = Engines::TYPE_SQLITE;

}
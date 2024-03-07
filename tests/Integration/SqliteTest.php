<?php

namespace JuanchoSL\Orm\Tests\Integration;

use JuanchoSL\Orm\engine\Engines;

class SqliteTest extends AbstractFunctional
{
    protected Engines $db_type = Engines::TYPE_SQLITE;

}
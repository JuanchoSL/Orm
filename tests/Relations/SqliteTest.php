<?php

namespace JuanchoSL\Orm\Tests\Relations;

use JuanchoSL\Orm\engine\Engines;

class SqliteTest extends AbstractRelationsTest
{

    protected Engines $db_type = Engines::TYPE_SQLITE;

}
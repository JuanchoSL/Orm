<?php

namespace JuanchoSL\Orm\Tests\Relations;

use JuanchoSL\Orm\engine\Engines;

class SqliteTest extends AbstractRelations
{

    protected Engines $db_type = Engines::TYPE_SQLITE;

}
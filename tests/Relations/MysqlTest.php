<?php

namespace JuanchoSL\Orm\Tests\Relations;

use JuanchoSL\Orm\engine\Engines;

class MysqlTest extends AbstractRelations
{

    protected Engines $db_type = Engines::TYPE_MYSQLI;
}
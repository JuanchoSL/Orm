<?php

namespace JuanchoSL\Orm\Tests\Relations;

use JuanchoSL\Orm\engine\Engines;

class MysqlTest extends AbstractRelationsTest
{

    protected Engines $db_type = Engines::TYPE_MYSQLI;
}
<?php

namespace JuanchoSL\Orm\Tests\Relations;

use JuanchoSL\Orm\engine\Engines;

class PostgresTest extends AbstractRelationsTest
{

    protected Engines $db_type = Engines::TYPE_POSTGRE;
}
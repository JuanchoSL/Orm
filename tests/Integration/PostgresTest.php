<?php

namespace JuanchoSL\Orm\Tests\Integration;

use JuanchoSL\Orm\engine\Engines;

class PostgresTest extends AbstractIntegrationTest
{
    protected Engines $db_type = Engines::TYPE_POSTGRE;

}
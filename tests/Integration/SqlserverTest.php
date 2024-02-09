<?php

namespace JuanchoSL\Orm\Tests\Integration;

use JuanchoSL\Orm\engine\Engines;

class SqlserverTest extends AbstractIntegrationTest
{
    protected Engines $db_type = Engines::TYPE_SQLSRV;

}
<?php

namespace JuanchoSL\Orm\Tests\Relations;

use JuanchoSL\Orm\engine\Engines;

class SqlserverTest extends AbstractRelationsTest
{

    protected Engines $db_type = Engines::TYPE_SQLSRV;
}
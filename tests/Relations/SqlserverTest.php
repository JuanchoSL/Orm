<?php

namespace JuanchoSL\Orm\Tests\Relations;

use JuanchoSL\Orm\engine\Engines;

class SqlserverTest extends AbstractRelations
{

    protected Engines $db_type = Engines::TYPE_SQLSRV;
}
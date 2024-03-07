<?php

namespace JuanchoSL\Orm\Tests\Functional;

use JuanchoSL\Orm\engine\Engines;

class SqlserverTest extends AbstractFunctional
{
    protected Engines $db_type = Engines::TYPE_SQLSRV;

}
<?php

namespace JuanchoSL\Orm\Tests\Functional;

use JuanchoSL\Orm\engine\Engines;

class OracleTest extends AbstractFunctionalTest
{
    protected Engines $db_type = Engines::TYPE_ORACLE;

}
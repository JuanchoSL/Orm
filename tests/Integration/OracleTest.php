<?php

namespace JuanchoSL\Orm\Tests\Integration;

use JuanchoSL\Orm\engine\Engines;

class OracleTest extends AbstractFunctional
{
    protected Engines $db_type = Engines::TYPE_ORACLE;

}
<?php

namespace JuanchoSL\Orm\Tests\Relations;

use JuanchoSL\Orm\engine\Engines;

class OracleTest extends AbstractRelationsTest
{

    protected Engines $db_type = Engines::TYPE_ORACLE;

    public function testOtherPk()
    {
        $this->markTestSkipped();
        $c = $this->db->execute("CREATE SEQUENCE other_id_seq START WITH 1 INCREMENT BY 1");
    }
}
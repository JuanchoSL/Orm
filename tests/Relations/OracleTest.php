<?php

namespace JuanchoSL\Orm\Tests\Relations;

use JuanchoSL\Orm\engine\Drivers\RDBMS;
use JuanchoSL\Orm\engine\Engines;
use JuanchoSL\Orm\Tests\TestDb;
use JuanchoSL\Orm\DatabaseFactory;
use JuanchoSL\Orm\engine\DbCredentials;

class OracleTest extends AbstractRelationsTest
{

    protected $db;
    private $loops = 3;

    public function setUp(): void
    {
        $credentials = new DbCredentials('localhost', 'SYS', 'oracle', 'SYSTEM');
        DatabaseFactory::init($credentials, Engines::TYPE_ORACLE, RDBMS::RESPONSE_OBJECT);
        try {
            $this->db = new TestDb();
        } catch (\Exception $ex) {
            echo __CLASS__ . "[{$ex->getCode()}] " . $ex->getMessage();
            exit;
        }
    }

    public function testOtherPk()
    {
        $this->markTestSkipped();
        $c = $this->db->execute("CREATE SEQUENCE other_id_seq START WITH 1 INCREMENT BY 1");
    }
}
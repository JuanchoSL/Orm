<?php

namespace JuanchoSL\Orm\Tests\Relations;

use JuanchoSL\Orm\DatabaseFactory;
use JuanchoSL\Orm\engine\Drivers\RDBMS;
use JuanchoSL\Orm\engine\Engines;
use JuanchoSL\Orm\Tests\TestDb;
use JuanchoSL\Orm\engine\DbCredentials;

class SqlserverTest extends AbstractRelationsTest
{

    protected $db;
    private $loops = 3;

    public function setUp(): void
    {
        $credentials = new DbCredentials('localhost', 'sa', 'Administrador1', 'master');
        DatabaseFactory::init($credentials, Engines::TYPE_SQLSRV, RDBMS::RESPONSE_OBJECT);
        try {
            $this->db = new TestDb();
        } catch (\Exception $ex) {
            echo __CLASS__ . "[{$ex->getCode()}] " . $ex->getMessage();
            exit;
        }
    }
}
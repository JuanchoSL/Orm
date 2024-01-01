<?php

namespace JuanchoSL\Orm\Tests\Functional;

use JuanchoSL\Orm\engine\Drivers\RDBMS;
use JuanchoSL\Orm\engine\Engines;
use JuanchoSL\Orm\Tests\TestDb;
use JuanchoSL\Orm\DatabaseFactory;
use JuanchoSL\Orm\engine\DbCredentials;

class PostgresTest extends AbstractFunctionalTest
{

    protected $db;
    private $loops = 3;

    public function setUp(): void
    {
        $credentials = new DbCredentials('localhost', 'root', 'root', 'test');
        DatabaseFactory::init($credentials, Engines::TYPE_POSTGRE, RDBMS::RESPONSE_OBJECT);
        try {
            $this->db = new TestDb();
        } catch (\Exception $ex) {
            echo __CLASS__ . "[{$ex->getCode()}] " . $ex->getMessage();
            exit;
        }
    }
}
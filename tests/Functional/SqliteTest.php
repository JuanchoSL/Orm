<?php

namespace JuanchoSL\Orm\Tests\Functional;

use JuanchoSL\Orm\engine\Drivers\RDBMS;
use JuanchoSL\Orm\DatabaseFactory;
use JuanchoSL\Orm\engine\Engines;
use JuanchoSL\Orm\Tests\TestDb;
use JuanchoSL\Orm\engine\DbCredentials;

class SqliteTest extends AbstractFunctionalTest
{

    protected $db;
    private $loops = 3;

    public function setUp(): void
    {
        $path = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'var';
        $credentials = new DbCredentials($path, '', '', 'test.db');
        DatabaseFactory::init($credentials, Engines::TYPE_SQLITE, RDBMS::RESPONSE_OBJECT);
        try {
            $this->db = new TestDb();
        } catch (\Exception $ex) {
            echo __CLASS__ . "[{$ex->getCode()}] " . $ex->getMessage();
            exit;
        }
    }

}
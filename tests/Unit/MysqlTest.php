<?php

namespace JuanchoSL\Orm\Tests\Unit;

use JuanchoSL\Orm\engine\Structures\FieldDescription;
use JuanchoSL\Orm\engine\Engines;

class MysqlTest extends AbstractUnitTest
{
    protected $db_type = Engines::TYPE_MYSQLI;

    public function queryCreateTable(): array
    {
        return [
            (new FieldDescription)->setName('id')->setType('integer')->setLength(6)->setNullable(false)->setKey(true),
            (new FieldDescription)->setName('test')->setType('varchar')->setLength(16)->setNullable(false),
            (new FieldDescription)->setName('dato')->setType('varchar')->setLength(16)->setNullable(false),
        ];
        return $query = "CREATE TABLE `test` (
            `id` smallint(6) NOT NULL PRIMARY KEY AUTO_INCREMENT,
            `test` varchar(16) NOT NULL,
            `dato` varchar(16) NOT NULL
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
    }
}
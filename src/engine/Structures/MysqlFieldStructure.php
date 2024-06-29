<?php

declare(strict_types=1);

namespace JuanchoSL\Orm\engine\Structures;

class MysqlFieldStructure
{
    public function getInt(int $length){}
    public function getText(int $length){}
    public function getDatetime(){}
    public function getDate(){}
    public function getBlob(){}
}
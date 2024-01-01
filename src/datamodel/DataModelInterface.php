<?php

namespace JuanchoSL\Orm\datamodel;

interface DataModelInterface
{
    public function getTableName();
    public function getPrimaryKeyValue();
    public function getPrimaryKeyName();
    public function delete();
    public function save();
    public static function all();
    public static function where(array ...$array_where);
    public static function model();
    public static function getInstance();
    public static function findByPk(int $id);
}
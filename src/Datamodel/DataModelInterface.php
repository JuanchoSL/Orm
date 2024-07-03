<?php

declare(strict_types=1);

namespace JuanchoSL\Orm\Datamodel;

use JuanchoSL\Orm\Querybuilder\QueryExecuter;

/**
 * @property string $connection_name
 * @property bool $lazyLoad
 * @property string $identifier
 * @property bool $loaded
 */
interface DataModelInterface
{
    public function getTableName(): string;
    public function getPrimaryKeyValue(): mixed;
    public function getPrimaryKeyName(): string;
    public function delete(): bool;
    public function save(): bool;
    public static function where(array ...$array_where): QueryExecuter;
    public static function model(): string;
    public static function getInstance(): DataModelInterface;
    public static function findByPk(int $id): DataModelInterface;
    public static function make(iterable $values): DataModelInterface;
}
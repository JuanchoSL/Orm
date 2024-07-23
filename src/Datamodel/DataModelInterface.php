<?php

declare(strict_types=1);

namespace JuanchoSL\Orm\Datamodel;


/**
 * @property string $connection_name
 * @property bool $lazyLoad
 * @property string $identifier
 * @property bool $loaded
 */
interface DataModelInterface
{

    /**
     * Returns the table name, if not setted, the model name, in lowercase will be returned
     * @return string The table name
     */
    public function getTableName(): string;

    /**
     * Returns the primary key of the table, if not setted, will be extracted from the table description
     * @return string The column name defined as primary key
     */
    public function getPrimaryKeyName(): string;


    /**
     * Returns the primary key value, if not setted, the column name will be extracted from the table description and returns his value
     * @return string The value os the column name defined as primary key
     */
    public function getPrimaryKeyValue(): mixed;

    /**
     * Delete the self instance, using his primary key value
     * @return bool The operation results
     */
    public function delete(): bool;

    /**
     * Save the self instance, saving his values. If primary key is defined, then perform an update, else do an insert into table
     * @return bool The result operation
     */
    public function save(): bool;

    /**
     * Create a query builder, using the entity table, in order to perform SELECT queries
     * @param array A sequence of conditions
     * @return QueryExecuter To append conditions, limits or execution final action
     */
    public static function where(array ...$array_where): QueryExecuter;
    //public static function model(): string;
    public static function getInstance(): DataModelInterface;
    public static function findByPk(int $id): DataModelInterface;
    public static function make(iterable $values): DataModelInterface;
}
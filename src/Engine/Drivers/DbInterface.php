<?php

namespace JuanchoSL\Orm\Engine\Drivers;

use JuanchoSL\Orm\Engine\Cursors\CursorInterface;
use JuanchoSL\Orm\Engine\Responses\AlterResponse;
use JuanchoSL\Orm\Engine\Responses\EmptyResponse;
use JuanchoSL\Orm\Engine\Responses\InsertResponse;
use JuanchoSL\Orm\Querybuilder\QueryBuilder;
use JuanchoSL\Orm\Querybuilder\Types\AbstractQueryBuilder;
use Psr\Log\LoggerAwareInterface;

interface DbInterface extends LoggerAwareInterface
{

    /**
     * Activa el modo debug, con información extendida en caso de tener el logger activo
     * @param bool $debug activa o desactiva la info extendida en el logger
     */
    public function setDebug(bool $debug = false): void;

    /**
     * Conecta al servidor y abre la base de datos
     */
    public function connect(): void;

    /**
     * Cierra la conexión mediante el puntero pasado por parámetro
     * @return bool Resultado de la operación
     */
    public function disconnect(): bool;

    /**
     * Devuelve el listado de nombres de las tablas del servidor y esquema seleccionado
     * @return array Array cuyo contenido es el listado de nombres de las tablas del esquema
     */
    public function getTables(): array;

    /**
     * Extracción de los nombres de los campos de la tabla
     * @return array Array con los nombres de los campos
     */
    public function columns(string $tabla): array;
    
    /**
     * Devuelve una matriz asociativa con la configuración de los campos de la
     * tabla, tipos, claves, valores por defecto...
     * @param string $tabla Nombre de la tabla
     * @return array Matriz asociativa con los parámetros de las columnas
     */
    public function describe(string $tabla): array;
    
    /**
     * Extracción de los nombres de las claves primarias de la tabla
     * @param string $tabla Nombre de la tabla
     * @return array Array con los nombres de las claves
     */
    public function keys(string $tabla): array;
    
    /**
     * Ejecuta una consulta sql en el servidor conectado
     * @param string|QueryBuilder|AbstractQueryBuilder $query Consulta a ejecutar
     * @return \JuanchoSL\Orm\Engine\Cursors\CursorInterface|\JuanchoSL\Orm\Engine\Responses\AlterResponse|\JuanchoSL\Orm\Engine\Responses\InsertResponse|\JuanchoSL\Orm\Engine\Responses\EmptyResponse Resultado de la operación
     */
    public function execute(string|QueryBuilder|AbstractQueryBuilder $query): CursorInterface|AlterResponse|InsertResponse|EmptyResponse;

    /**
     * Escapa valores introducidos en campos de texto para incluir en consultas
     * @param string $value Campo insertado en un input
     * @return string Cadena escapada para evitar SQL Injection
     */
    public function escape(string $value): string;
}

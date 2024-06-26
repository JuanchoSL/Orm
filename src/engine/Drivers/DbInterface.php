<?php

namespace JuanchoSL\Orm\engine\Drivers;

use JuanchoSL\Orm\engine\Cursors\CursorInterface;
use JuanchoSL\Orm\engine\Responses\AlterResponse;
use JuanchoSL\Orm\engine\Responses\EmptyResponse;
use JuanchoSL\Orm\engine\Responses\InsertResponse;
use JuanchoSL\Orm\querybuilder\QueryBuilder;
use JuanchoSL\Orm\querybuilder\Types\AbstractQueryBuilder;
use Psr\Log\LoggerAwareInterface;

/**
 * Interficie para implementar clases de gestión de tablas en SGBD
 *
 * @author Juan Sánchez Lecegui
 * @version 1.2
 */
interface DbInterface extends LoggerAwareInterface
{

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
     * Devuelve el nombre de la tabla sobre la que se está trabajando
     * @return string Nombre de la tabla
     */
    //public function getTable(): string;

    /**
     * Permite cambiar la tabla sobre la que se va a trabajar
     * @param string $tabla Nombre de la tabla
     */
    //public function setTable(string $tabla): static;
    
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
     * @param string|\JuanchoSL\Orm\querybuilder\QueryBuilder $query Consulta a ejecutar
     * @return \JuanchoSL\Orm\engine\Cursors\CursorInterface|\JuanchoSL\Orm\engine\Responses\AlterResponse|\JuanchoSL\Orm\engine\Responses\InsertResponse|\JuanchoSL\Orm\engine\Responses\EmptyResponse Resultado de la operación
     */
    public function execute(string|QueryBuilder $query): CursorInterface|AlterResponse|InsertResponse|EmptyResponse;

    /**
     * Escapa valores introducidos en campos de texto para incluir en consultas
     * @param string $value Campo insertado en un input
     * @return string Cadena escapada para evitar SQL Injection
     */
    public function escape(string $value): string;
}

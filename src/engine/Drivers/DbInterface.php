<?php

namespace JuanchoSL\Orm\engine\Drivers;

use JuanchoSL\Orm\engine\Cursors\CursorInterface;
use JuanchoSL\Orm\querybuilder\QueryBuilder;

/**
 * Interficie para implementar clases de gestión de tablas en SGBD
 *
 * @author Juan Sánchez Lecegui
 * @version 1.2
 */
interface DbInterface
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
    public function getTable(): string;

    /**
     * Permite cambiar la tabla sobre la que se va a trabajar
     * @param string $tabla Nombre de la tabla
     */
    public function setTable(string $tabla): void;

    /**
     * Extrae el contenido completo de la tabla, o las tuplas que cumplan la condición
     * si se especifica
     * @param mixed $where_array Matriz asociativo con las condiciones de la query.
     * @internal Podemos pasar una matriz $where['AND|OR|LIKE'][$key][$value].
     * Para querys más complejas podemos pasar un string directamente
     * @param string $order Campo de ordenación de los resultados de la query
     * @param integer $pagina Número de página a mostrar
     * @param integer $limit Límite de las tuplas devueltas
     * @return mixed Matriz de las tuplas devueltas
     */
    //public function select($where_array = array(), $order = null, $pagina = 0, $limit = null): CursorInterface;

    /**
     * Inserta una tupla dentro de la tabla
     * @param mixed $values Valores a insertar, puede se un array asociativo o
     * una cadena con los valores en el orden de la tabla
     */
    //public function insert(array $values): int;

    /**
     * Actualiza los valores de una tabla con los pasados por parámetro, pudiendo
     * ser éste una cadena o un array asociativo campo = "valor".
     * @param mixed $values String o array asociativo con los campos a actualizar
     * @param array $where_array Condición que deben cumplir las tuplas a actualizar
     */
    //public function update(array $values, array $where_array): int;

    /**
     * Elimina los registros de la tabla que cumplan la condición pasada por
     * parámetro o todo el contenido de la tabla en caso de no especificarse.
     * @param string $where Condición de los registros a borrar
     */
    public function delete(array $where): int;

    public function truncate(): bool;
    
    public function drop();
    
    /**
     * Extracción de los nombres de los campos de la tabla
     * @return mixed Array con los nombres de los campos
     */
    public function columns(string $tabla = null): array;
    
    /**
     * Devuelve una matriz asociativa con la configuración de los campos de la
     * tabla, tipos, claves, valores por defecto...
     * @param string $tabla Nombre de la tabla
     * @return array Matriz asociativa con los parámetros de las columnas
     */
    public function describe(string $tabla = null): array;
    
    /**
     * Extracción de los nombres de las claves primarias de la tabla
     * @param string $tabla Nombre de la tabla
     * @return mixed Array con los nombres de las claves
     */
    public function keys(string $tabla = null): array;
    
    /**
     * Ejecuta una consulta sql en el servidor conectado
     * @param string $tabla Nombre de la tabla
     * @param string|\JuanchoSL\Orm\querybuilder\QueryBuilder $query Consulta a ejecutar
     * @return \JuanchoSL\Orm\engine\Cursors\CursorInterface Resultado de la operación
     */
    public function execute(string|QueryBuilder $query): CursorInterface;

    /**
     * Escapa valores introducidos en campos de texto para incluir en consultas
     * @param string $value Campo insertado en un input
     * @return string Cadena escapada para evitar SQL Injection
     */
    public function escape(string $value): string;

    public function affectedRows(): int;

    public function lastInsertedId(): int;
}

<?php

namespace JuanchoSL\Orm\Engine\Drivers;

use JuanchoSL\Orm\DatabaseFactory;
use JuanchoSL\Orm\engine\Cursors\CursorInterface;
use JuanchoSL\Orm\engine\DbCredentials;
use JuanchoSL\Orm\querybuilder\QueryBuilder;

abstract class RDBMS
{

    /**
     * Versión mínima requerida de PHP
     * @var string PHP_MIN_VERSION
     */
    const PHP_MIN_VERSION = "5.0.0";
    const RESPONSE_OBJECT = 'object';
    const RESPONSE_ASSOC = 'assoc';
    const RESPONSE_ROWS = 'rows';

    protected $sqlBuilder;
    protected $linkIdentifier;
    protected $typeReturn;
    protected $nResults = 0;
    protected $nResultsPagination = null;
    protected $variables;
    protected $tabla;
    protected $describe = [];
    protected $columns = [];
    protected $keys = [];
    protected $lastInsertedId;
    protected $cursor;
    protected $credentials;

    function __construct(DbCredentials $credentials, $typeReturn = 'object')
    {
        if (extension_loaded($this->requiredModule)) {
            $this->credentials = $credentials;
            $this->typeReturn = $typeReturn;
            $this->connect();
            $this->sqlBuilder = new QueryBuilder();
        } else {
            throw new \Exception($this->requiredModule);
        }
    }

    public function getTypeReturn()
    {
        return $this->typeReturn;
    }

    public function getNResults()
    {
        return $this->nResults;
    }

    public function getNResultsNoPagination()
    {
        return $this->nResultsPagination;
    }

    public function getTable(): string
    {
        return $this->tabla;
    }
    public function getKeys(): array
    {
        return $this->keys[$this->tabla];
    }

    /**
     * Permite cambiar la tabla sobre la que se va a trabajar
     * @param string $tabla Nombre de la tabla
     */
    public function setTable(string $tabla): void
    {
        $this->tabla = $tabla;
        $this->describe();
        $this->columns();
        $this->keys();
    }

    public function cleanFields(array $camps = array(), string $table = null)
    {
        if (count($camps) > 0) {
            if (empty($table)) {
                $table = $this->tabla;
            }
            foreach ($camps as $key => $value) {
                if (is_numeric($key)) {
                    continue;
                } elseif (in_array($key, array('AND', 'OR', 'LIKE', 'IN', 'NOT IN'))) {
                    $vals = $value;
                    foreach ($vals as &$val) {
                        if (is_array($val)) {
                            $val = $this->cleanFields($val);
                        }
                    }
                } else if (!isset($this->columns[$table]) or !in_array(strtolower($key), $this->columns[$table])) {
                    unset($camps[$key]);
                } else {
                    $value = $this->escape($value);
                    $field = $this->describe[$table][strtolower($key)]->getName();
                    unset($camps[$key]);
                    $camps[$field] = $value;
                    //$camps[$key] = $this->escape($value);
                }
            }
            if (count($camps) > 0) {
                return $camps;
            }
        }
        return $camps;
    }

    /**
     * Comprueba si el valor pasado es un array o una cadena y lo devuelve como string
     * @param mixed $values Parámetros a comprobar y concatenar
     * @param string $envoltorio Caracter que separará cada variable retornada, normalmente "'"
     * @param bool $indices True si se devuelve una asociación indice=valor
     * False o null si solo se devuelve una cadena de valores
     * @return string Cadena con el contenido especificado del parámetro
     */
    protected function toString(array $values, $envoltorio = null, $indices = false)
    {
        $response = [];
       // $values = $this->cleanFields($values);
        foreach ($values as $key => $value) {
            $response[] = $this->mountComparation("{$key}={$value}");
        }
        return implode(',', $response);
    }

    public function columns(string $tabla = null): array
    {
        if (empty($tabla)) {
            $tabla = $this->tabla;
        }
        if (!array_key_exists($tabla, $this->describe)) {
            $this->describe($tabla);
        }
        return $this->columns[$tabla] = array_keys($this->describe[$tabla]);
    }

    /**
     * Extracción de los nombres de las claves primarias de la tabla
     * @return mixed Array con los nombres de las claves
     */
    public function keys(string $tabla = null): array
    {
        if (empty($tabla)) {
            $tabla = $this->tabla;
        }
        if (!array_key_exists($tabla, $this->keys)) {
            $this->describe($tabla);
        }

        $this->keys[$tabla] = [];
        foreach ($this->describe[$tabla] as $key) {
            if ($key->isKey()) {
                $this->keys[$tabla][] = $key->getName();
            }
        }
        return $this->keys[$tabla];
    }

    public function __destruct()
    {
        //$this->freeCursor();
        $this->disconnect();
        unset($this->linkIdentifier);
    }
/*
    public function select($where_array = array(), $order = null, $page = 0, $limit = null, $inner = array()): CursorInterface
    {
        //$where_array = $this->cleanFields($where_array);
        if (is_numeric($limit)) {
            $builder = DatabaseFactory::queryBuilder()->select(['COUNT(*) AS t'])->from($this->tabla)->join($inner)->where($where_array);
            $response = $this->execute($builder)->next(self::RESPONSE_ASSOC); //"SELECT COUNT(*) AS total FROM " . $this->tabla . $this->mountWhere($where_array))
            $nResultsPagination = $response['t'];
        }
        $builder = DatabaseFactory::queryBuilder()->select()->from($this->tabla)->join($inner)->where($where_array)->orderBy($order)->limit($limit, $page);
        $return = $this->execute($builder);
        $this->nResults = $return->count();
        $this->nResultsPagination = (empty($nResultsPagination)) ? $this->nResults : $nResultsPagination;
        return $return;
    }
*/
/**
 * Inserta una tupla dentro de la tabla
 * @param mixed $values Valores a insertar, puede ser un array nominal o uno
 * asociativo o una cadena con los valores en el orden de la tabla
 * @return int Código de la primary key del nuevo registro
 */

public function insert(array $values): int
    {
        //$values = $this->cleanFields($values);
        $builder = DatabaseFactory::queryBuilder()->insert($values)->into($this->tabla);
        $this->execute($builder);
        return $this->lastInsertedId();
    }
    /**
     * Actualiza los valores de una tabla con los pasados por parámetro, pudiendo
     * ser éste una cadena o un array asociativo campo = "valor".
     * @param mixed $values String o array asociativo con los campos a actualizar
     * @param mixed $where_array Matriz asociativo con las condiciones de la query.
     * @return int Número de filas afectadas
     * @internal Podemos pasar una matriz $where['AND|OR|LIKE'][$key]=$value.
     * Para querys más complejas podemos pasar un string directamente
     * @return string Resultado de la operación
     */
    /*
    public function update(array $values, array $where_array): int
    {
        //$values = $this->cleanFields($values);
        $builder = DatabaseFactory::queryBuilder()->update($values)->table($this->tabla)->where($where_array);
        $this->execute($builder);
        return $this->affectedRows();
    }
*/
    /**
     * Elimina los registros de la tabla que cumplan la condición pasada por
     * parámetro o todo el contenido de la tabla en caso de no especificarse.
     * @param array $where_array Condición de los registros a borrar.
     * @return int Número de filas afectadas.
     * @internal Usar cuidadosamente. No pasar parámetros truncará la tabla!!!
     * @internal Podemos pasar una matriz $where['AND|OR|LIKE'][$key]=$value.
     * Para querys más complejas podemos pasar un string directamente
     */
    public function delete(array $where_array): int
    {
        $builder = DatabaseFactory::queryBuilder()->delete()->from($this->tabla);
        if (!empty($where_array)) {
            $builder->where($where_array);
        }
        $this->execute($builder);
        return $this->affectedRows();
    }

    public function truncate(): bool
    {
        $builder = DatabaseFactory::queryBuilder()->truncate()->table($this->tabla);
        $this->execute($builder);
        return true;
        return $this->affectedRows();
    }

    public function drop()
    {
        return $this->execute(DatabaseFactory::queryBuilder()->drop()->table($this->tabla));
    }

    /**
     * Devuelve el listado de nombres de las tablas del servidor y esquema seleccionado
     * @return mixed Array cuyo contenido es el listado de nombres de las tablas del esquema
     */
    protected function extractTables($sql)
    {
        $taules = array();
        $result = $this->execute($sql);
        while ($linea = $result->next(self::RESPONSE_ROWS)) {
            $taules[] = $linea[0];
        }
        $result->free();
        /*
        while ($linea = $this->nextResult($result, self::RESPONSE_ROWS)) {
            $taules[] = $linea[0];
        }
        if ($result) {
            $this->freeResult($result);
        }*/
        return $taules;
    }

    protected function parseQuery(QueryBuilder|string $query)
    {
        if (is_object($query)) {
            $this->setTable($query->table);
            //$builderType = get_class($this->sqlBuilder);
            //$queryType = get_class($query);
            //if ($queryType == $builderType || $query instanceof $builderType) {
            if (!is_null($query->operation) && method_exists($this, 'parse' . ucfirst(strtolower($query->operation)))) {
                $query = call_user_func(array($this, 'parse' . ucfirst(strtolower($query->operation))), $query);
            } else {
                $query = $this->getQuery($query);
            }
            //}
        }

        $this->lastInsertedId = null;
        $this->nResultsPagination = null;
        $this->nResults = null;
        $this->cursor = null;
        return $query;
    }
    public function lastInsertedId()
    {
        return $this->lastInsertedId;
    }

    public function affectedRows(): int
    {
        return $this->nResults;
    }

    /**
     * Escapa valores introducidos en campos de texto para incluir en consultas
     * @param string $value Campo insertado en un input
     * @return string Cadena escapada para evitar SQL Injection
     */
    public function escape(string $str): string
    {
        if (is_array($str))
            return array_map(__METHOD__, $str);

        if (!empty($str) && is_string($str)) {
            $str = stripslashes($str);
            return str_replace(array('\\', "\0", "\n", "\r", "'", '"', "\x1a"), array('\\\\', '\\0', '\\n', '\\r', "\\'", '\\"', '\\Z'), $str);
        }
        return $str;
    }

}

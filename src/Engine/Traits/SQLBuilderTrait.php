<?php

declare(strict_types=1);

namespace JuanchoSL\Orm\Engine\Traits;

use JuanchoSL\Exceptions\PreconditionFailedException;
use JuanchoSL\Exceptions\PreconditionRequiredException;
use JuanchoSL\Orm\Querybuilder\QueryActionsEnum;
use JuanchoSL\Orm\Querybuilder\QueryBuilder;
use JuanchoSL\Orm\Querybuilder\Types\AbstractQueryBuilder;

trait SQLBuilderTrait
{

    protected function getQuery(AbstractQueryBuilder|QueryBuilder $queryBuilder): string
    {
        $condition = null;
        if ($queryBuilder->operation->value != QueryActionsEnum::DESCRIBE->value && !empty($queryBuilder->condition)) {
            $this->setTable($queryBuilder->table);
            $condition = $this->mountWhere($queryBuilder->condition, $queryBuilder->table);
        }
        $join = (isset($queryBuilder->join) && is_array($queryBuilder->join) && count($queryBuilder->join) > 0) ? " " . implode(" ", $queryBuilder->join) : null;

        switch ($queryBuilder->operation) {
            case QueryActionsEnum::SELECT:
                $group = (isset($queryBuilder->group) && is_string($queryBuilder->group)) ? " GROUP BY " . $queryBuilder->group : "";
                $order = (isset($queryBuilder->order) && is_string($queryBuilder->order)) ? " ORDER BY " . $queryBuilder->order : "";
                $limit = (!empty($queryBuilder->limit)) ? $this->mountLimit($queryBuilder->limit[0], $queryBuilder->limit[1]) : '';
                $camps = (isset($queryBuilder->camps) && is_array($queryBuilder->camps) && count($queryBuilder->camps) > 0) ? implode(',', $queryBuilder->camps) : '*';
                $table = (!empty($queryBuilder->table)) ? "FROM " . $queryBuilder->table : '';
                $a = "{$queryBuilder->operation->value} {$camps} " . $table . $join . $condition . $group . $order . $limit . $queryBuilder->extraQuery;
                return $a;

            case QueryActionsEnum::INSERT:
                $values = $this->cleanFields($queryBuilder->table, $queryBuilder->values);

                $valuesStr = "(" . implode(",", array_keys($values)) . ") VALUES ('" . implode("', '", array_values($values)) . "')";
                return $queryBuilder->operation->value . " INTO " . $queryBuilder->table . " " . $valuesStr . $queryBuilder->extraQuery;

            case QueryActionsEnum::UPDATE:
                $response = [];
                foreach ($queryBuilder->values as $key => $value) {
                    $response[] = $this->mountAssignament($queryBuilder->table, $key, $value);
                }
                if (empty($response)) {
                    throw new PreconditionRequiredException("No valid data to save");
                }
                return $queryBuilder->operation->value . " " . $queryBuilder->table . " SET " . implode(',', $response) . " " . $condition;

            case QueryActionsEnum::TRUNCATE:
            case QueryActionsEnum::DROP:
                return $queryBuilder->operation->value . " TABLE " . $queryBuilder->table;

            case QueryActionsEnum::DELETE:
                if (empty($condition)) {
                    //throw new PreconditionRequiredException("WHERE condition is empty");
                }
                return $queryBuilder->operation->value . " FROM " . $queryBuilder->table . $condition;

            case QueryActionsEnum::DESCRIBE:
            case QueryActionsEnum::PRAGMA:
                return $queryBuilder->operation->value . " " . $queryBuilder->table;

            case QueryActionsEnum::EXEC:
                $camps = (isset($queryBuilder->camps) && is_array($queryBuilder->camps) && count($queryBuilder->camps) > 0) ? implode(',', $queryBuilder->camps) : 'TABLES';
                return $queryBuilder->operation->value . " {$camps} " . $queryBuilder->table;

            case QueryActionsEnum::SHOW:
                $camps = (isset($queryBuilder->camps) && is_array($queryBuilder->camps) && count($queryBuilder->camps) > 0) ? implode(',', $queryBuilder->camps) : 'TABLES';
                return $queryBuilder->operation->value . " {$camps} FROM " . $queryBuilder->table;

            default:
                $a = $queryBuilder->operation->value . " " . implode(',', $queryBuilder->camps) . " " . $queryBuilder->table;
                return $a;
        }
    }

    /**
     * Monta un string para pasar como condición where a una query.
     * Si queremos pasar varios parámetor condicionales, podemos
     * pasar una matriz asociativa del tipo $array['AND|OR'] campo='valor'.
     * Tambien podemos pasar un array nominal con cadenas condicionales completas,
     * mezclar cualquiera de las anteriores opciones o un string con la condicional
     * de la consulta completa.
     * @param array<int,array<string, array<int, array<int, scalar>>>> $where_array Condicionales a montar
     * @return string Tabla de referencia
     */
    protected function mountWhere(array $where_array, string $tabla)
    {
        $where = "";
        if (is_string($where_array)) {
            $where_array = [$where_array];
        }
        if (is_array($where_array) && count($where_array) > 0) {
            foreach ($where_array as $blocks) {
                foreach ($blocks as $separator => $comparations) {
                    if (!empty($where)) {
                        $where .= " {$separator} ";
                    }
                    $where .= "(";
                    foreach ($comparations as $comparation) {
                        if (empty($comparation[1]) && !isset($comparation[2])) {
                            $mounted = $this->mountComparation($comparation[0], $tabla);
                        } else {
                            list($field, $value) = $comparation;
                            if (is_null($value)) {
                                if (isset($comparation[2]) && is_bool($comparation[2])) {
                                    $comparator = ($comparation[2]) ? 'IS NULL' : 'IS NOT NULL';
                                } else {
                                    $comparator = (empty($comparation[2])) ? 'IS NULL' : $comparation[2];
                                }
                            } elseif (is_array($value) or $value instanceof QueryBuilder) {
                                if (isset($comparation[2]) && is_bool($comparation[2])) {
                                    $comparator = ($comparation[2]) ? 'IN' : 'NOT IN';
                                } else {
                                    $comparator = (empty($comparation[2])) ? 'IN' : $comparation[2];
                                }
                                if (is_array($value)) {
                                    foreach ($value as $index => $val) {
                                        if (is_string($val)) {
                                            $value[$index] = $this->escape($val);
                                        }
                                    }
                                    $value = "('" . implode("','", $value) . "')";
                                } else {
                                    $value = "(" . $this->getQuery($value) . ")";
                                }
                            } else {
                                if (!isset($comparation[2])) {
                                    $comparation[2] = '=';
                                }
                                if (is_bool($comparation[2])) {
                                    $comparator = ($comparation[2]) ? '=' : '!=';
                                } else {
                                    $comparator = (empty($comparation[2])) ? '=' : $comparation[2];
                                }
                            }
                            $mounted = $this->mountAssignament($tabla, $field, $value, $comparator);
                        }
                        if (!empty($mounted)) {
                            $where .= $mounted . " AND ";
                        }
                    }
                    if (substr($where, -1) != '(') {
                        $where = substr($where, 0, -5);
                        $where .= ")";
                    }
                }
            }
        }
        if (empty($where)) {
            $where = '1=1';
        }
        $where = " WHERE {$where}";
        $this->log(__FUNCTION__, 'debug', ['table' => $tabla, 'initial' => $where_array, 'final' => $where]);
        return $where;
    }

    protected function mountComparation(string $ostring, string $tabla): string
    {
        $string = stripslashes(trim($ostring));
        $last_char = substr($string, -1, 1);
        if (substr_count($string, $last_char) == 2) {
            if ($last_char == '"') {
                $string = str_replace('"', "", $string);
            } elseif ($last_char == "'") {
                $string = str_replace("'", "", $string);
            }
        }
        preg_match("/([\w.]+)(\W+)(.*)/", $string, $matches);
        list($string, $key, $comparator, $value) = $matches;
        $this->log(__FUNCTION__, 'debug', ['table' => $tabla, 'initial' => $ostring, 'modified' => $string, 'value' => $value, 'matches' => $matches]);
        return $this->mountAssignament($tabla, $key, $value, $comparator);
    }

    protected function mountAssignament(string $tabla, string $key, string|int|null $value, string $comparator = '='): string|false
    {
        if ($strict = substr_count($key, '(') == 0 || substr_count($key, '(') != substr_count($key, ')')) {
            $key = strtolower($key);
        }

        $this->log(__FUNCTION__, 'debug', ['table' => $tabla, 'key' => $key, 'comparator' => $comparator, 'value' => $value]);
        $sub_table = (strpos($key, '.') !== false) ? substr($key, 0, strpos($key, '.')) : $tabla;
        $sub_key = (strpos($key, '.') !== false) ? substr($key, strpos($key, '.') + 1) : $key;
        if (!$strict || array_key_exists($sub_key, $this->describe($sub_table))) {
            if (!is_null($value) && stripos($comparator, 'NULL') === false && stripos($comparator, 'IN') === false) {
                if (!$strict || empty($this->describe[$sub_table][$sub_key]->getType()) || (isset($this->describe[$sub_table][$sub_key]) && (stripos($this->describe[$sub_table][$sub_key]->getType(), 'char') !== false || stripos($this->describe[$sub_table][$sub_key]->getType(), 'text') !== false))) {
                    $value = (string) $value;
                    if (substr_count($value, '(') == 0 || substr_count($value, '(') != substr_count($value, ')')) {
                        $value = $this->escape($value);
                        $value = "'{$value}'";
                    }
                }
            }
            $key_name = (isset($this->describe[$sub_table][$sub_key])) ? $this->describe[$sub_table][$sub_key]->getName() : $sub_key;
            $key = ($sub_key != $key) ? $sub_table . '.' . $key_name : $key_name;
            return "{$key} {$comparator} {$value}";
        }

        $e = new PreconditionFailedException("The field {$key} does not exists on the table {$tabla}");
        $this->log($e, 'error', ['exception' => $e]);
        throw $e;
    }

    protected function mountLimit(int $limit, int $page): string
    {
        return " LIMIT " . $limit . " OFFSET " . (intval($page) * $limit);
        return " LIMIT " . (intval($page) * $limit) . "," . $limit;
        return "SELECT * FROM (SELECT t.*, ROW_NUMBER() OVER (ORDER BY " . $order . ") AS MyRow FROM " . $sqlBuilder->table . " t " . $join . " " . $where . ") AS totalNoPagination WHERE MyRow BETWEEN " . $inicio . " AND " . $limit;
        return "SELECT * FROM (SELECT t.*, ROW_NUMBER() OVER (ORDER BY " . $order . ") AS MyRow FROM " . $sqlBuilder->table . " t " . $join . " " . $where . ") AS totalNoPagination WHERE MyRow BETWEEN " . $inicio . " AND " . $limit;
        return "SELECT * FROM (SELECT t.*, Row_Number() OVER (ORDER BY " . $order . ") MyRow FROM " . strtoupper($sqlBuilder->table) . " t " . $join . " " . $where . ") WHERE MyRow BETWEEN " . $inicio . " AND " . $limit;
    }
}

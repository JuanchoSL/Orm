<?php

namespace JuanchoSL\Orm\querybuilder;

use JuanchoSL\Exceptions\PreconditionRequiredException;
use JuanchoSL\Orm\querybuilder\Types\AbstractQueryBuilder;

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
                $order = (isset($queryBuilder->order) && is_string($queryBuilder->order)) ? " ORDER BY " . $queryBuilder->order : "";
                $limit = (!empty($queryBuilder->limit)) ? $this->mountLimit($queryBuilder->limit[0], $queryBuilder->limit[1]) : '';
                $camps = (isset($queryBuilder->camps) && is_array($queryBuilder->camps) && count($queryBuilder->camps) > 0) ? implode(',', $queryBuilder->camps) : '*';
                $table = (!empty($queryBuilder->table)) ? "FROM " . $queryBuilder->table : '';
                $a = "{$queryBuilder->operation->value} {$camps} " . $table . $join . $condition . $order . $limit . $queryBuilder->extraQuery;
                return $a;

            case QueryActionsEnum::INSERT:
                $values = $this->cleanFields($queryBuilder->table, $queryBuilder->values);

                $valuesStr = "(" . implode(",", array_keys($values)) . ") VALUES ('" . implode("', '", array_values($values)) . "')";
                return $queryBuilder->operation->value . " INTO " . $queryBuilder->table . " " . $valuesStr . $queryBuilder->extraQuery;

            case QueryActionsEnum::UPDATE:
                $response = [];
                foreach ($queryBuilder->values as $key => $value) {
                    $response[] = $this->mountComparation("{$key}={$value}", $queryBuilder->table);
                }
                if (empty($response)) {
                    throw new PreconditionRequiredException("No valid data to save");
                }
                return $queryBuilder->operation->value . " " . $queryBuilder->table . " SET " . implode(',', $response) . " " . $condition;
            //return $queryBuilder->operation->value . " " . $queryBuilder->table . " SET " . $this->toString($queryBuilder->values, "'", true) . " " . $condition;

            case QueryActionsEnum::TRUNCATE:
            case QueryActionsEnum::DROP:
                return $queryBuilder->operation->value . " TABLE " . $queryBuilder->table;

            case QueryActionsEnum::DELETE:
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
        $where = " WHERE 1=1";
        if (is_string($where_array)) {
            $where_array = [$where_array];
        }
        if (is_array($where_array) && count($where_array) > 0) {
            foreach ($where_array as $blocks) {
                foreach ($blocks as $separator => $comparations) {
                    $where .= " {$separator} (";
                    foreach ($comparations as $comparation) {
                        //if (!isset($comparation[1])) {
                        if (empty($comparation[1]) && !isset($comparation[2])) {
                            $where .= $this->mountComparation($comparation[0], $tabla);
                        } else {
                            list($field, $value) = $comparation;
                            $sub_table = (strpos($field, '.') !== false) ? substr($field, 0, strpos($field, '.')) : $tabla;
                            $sub_field = (strpos($field, '.') !== false) ? substr($field, strpos($field, '.') + 1) : $field;
                            if (!in_array(strtolower($sub_field), $this->columns($sub_table))) {
                                continue;
                            }
                            $new_field = $this->describe[$sub_table][strtolower($sub_field)]->getName();
                            $new_field = ($sub_field != $field) ? $sub_table . '.' . $new_field : $field;
                            if (is_array($value)) {
                                if (isset($comparation[2]) && is_bool($comparation[2])) {
                                    $comparator = ($comparation[2]) ? ' IN ' : ' NOT IN ';
                                } else {
                                    $comparator = (empty($comparation[2])) ? ' IN ' : $comparation[2];
                                }
                                //$comparator = ($comparator) ? 'IN' : 'NOT IN';
                                foreach ($value as $index => $val) {
                                    $value[$index] = $this->escape($val);
                                }
                                $where .= $new_field . " " . $comparator . " ('" . implode("','", $value) . "')";
                            } elseif (is_null($value)) {
                                if (isset($comparation[2]) && is_bool($comparation[2])) {
                                    $comparator = ($comparation[2]) ? ' IS NULL ' : ' IS NOT NULL ';
                                } else {
                                    $comparator = (empty($comparation[2])) ? ' IS NULL ' : $comparation[2];
                                }
                                //$comparator = ($comparator) ? 'IS NULL' : 'IS NOT NULL';
                                $where .= $new_field . " " . $value . " " . $comparator;
                            } elseif ($value instanceof QueryBuilder) {
                                if (isset($comparation[2]) && is_bool($comparation[2])) {
                                    $comparator = ($comparation[2]) ? ' IN ' : ' NOT IN ';
                                } else {
                                    $comparator = (empty($comparation[2])) ? ' IN ' : $comparation[2];
                                }
                                $where .= $new_field . " " . $comparator . " (" . $this->getQuery($value) . ")";
                            } else {
                                if (isset($comparation[2]) && is_bool($comparation[2])) {
                                    $comparator = ($comparation[2]) ? '=' : '!=';
                                } else {
                                    $comparator = (empty($comparation[2])) ? '=' : $comparation[2];
                                }
                                $where .= $this->mountComparation($new_field . $comparator . $value, $tabla);
                            }
                        }
                        $where .= " AND ";
                    }
                    $where = substr($where, 0, -5);
                    $where .= ")";
                }
            }
        }
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
                //$string = trim($string, $last_char);
            } elseif ($last_char == "'") {
                //$string = trim($string, $last_char);
                $string = str_replace("'", "", $string);
            }
        }
        preg_match("/([\w.]+)(\W+)(.*)/", $string, $matches);

        list($string, $key, $comparator, $value) = $matches;
        $this->log(__FUNCTION__, 'debug', ['table' => $tabla, 'initial' => $ostring, 'modified' => $string, 'value' => $value, 'matches' => $matches]);
        $value = $this->escape($value);
        $key = strtolower($key);
        $this->log(__FUNCTION__, 'debug', ['table' => $tabla, 'initial' => $ostring, 'modified' => $string, 'value' => $value, 'matches' => $matches]);
        $sub_table = (strpos($key, '.') !== false) ? substr($key, 0, strpos($key, '.')) : $tabla;
        $sub_key = (strpos($key, '.') !== false) ? substr($key, strpos($key, '.') + 1) : $key;
        if (is_array($this->describe) && array_key_exists($sub_key, $this->describe[$sub_table])) {
            if (empty($this->describe[$sub_table][$sub_key]->getType()) || stripos($this->describe[$sub_table][$sub_key]->getType(), 'char') !== false || stripos($this->describe[$sub_table][$sub_key]->getType(), 'text') !== false) {
                //$value = str_replace("\\'", "\'", $value);
                $value = "'{$value}'";
            }
            $key_name = $this->describe[$sub_table][$sub_key]->getName();
            $key = ($sub_key != $key) ? $sub_table . '.' . $key_name : $key_name;
        }

        return "{$key} {$comparator} {$value}";
    }

    protected function mountLimit(int $limit, int $page): string
    {
        return " LIMIT " . (intval($page) * $limit) . "," . $limit;
    }
}

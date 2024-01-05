<?php

namespace JuanchoSL\Orm\querybuilder;

trait SQLBuilderTrait
{

    protected function getQuery(QueryBuilder $queryBuilder): string
    {
        $this->describe($queryBuilder->table);
        $condition = $this->mountWhere($queryBuilder->condition);
        $join = (isset($queryBuilder->join) && is_array($queryBuilder->join) && count($queryBuilder->join) > 0) ? " " . implode(" ", $queryBuilder->join) : null;

        switch ($queryBuilder->operation) {
            case QueryBuilder::MODE_SELECT:
                $order = (isset($queryBuilder->order) && is_string($queryBuilder->order)) ? " ORDER BY " . $queryBuilder->order : "";
                $limit = (!empty($queryBuilder->limit)) ? $this->mountLimit($queryBuilder->limit[0], $queryBuilder->limit[1]) : '';
                $camps = (isset($queryBuilder->camps) && is_array($queryBuilder->camps) && count($queryBuilder->camps) > 0) ? implode(',', $queryBuilder->camps) : '*';
                $table = (!empty($queryBuilder->table)) ? "FROM " . $queryBuilder->table : '';
                $a = "{$queryBuilder->operation} {$camps} " . $table . $join . $condition . $order . $limit . $queryBuilder->extraQuery;
                //print_r($a) . PHP_EOL;
                return $a;

            case QueryBuilder::MODE_INSERT:
                $values =$this->cleanFields($queryBuilder->values, $queryBuilder->table);
                $valuesStr = "(" . implode(",", array_keys($values)) . ") VALUES ('" . implode("', '", array_values($values)) . "')";
                return $queryBuilder->operation . " INTO " . $queryBuilder->table . " " . $valuesStr . $queryBuilder->extraQuery;

            case QueryBuilder::MODE_UPDATE:
                return $queryBuilder->operation . " " . $queryBuilder->table . " SET " . $this->toString($queryBuilder->values, "'", true) . " " . $condition;

            case QueryBuilder::MODE_TRUNCATE:
            case QueryBuilder::MODE_DROP:
                return $queryBuilder->operation . " TABLE " . $queryBuilder->table;

            case QueryBuilder::MODE_DELETE:
                return $queryBuilder->operation . " FROM " . $queryBuilder->table . $condition;

            default:
                $a = $queryBuilder->operation . " " . implode(',', $queryBuilder->camps) . " " . $queryBuilder->table;
                //print_r($a);exit;
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
     * @param mixed $where_array Condicionales a montar
     * @return string Condición where montada
     */
    protected function mountWhere($where_array)
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
                        if (empty($comparation[1]) && empty($comparation[2])) {
                            $where .= $this->mountComparation($comparation[0]);
                        } else {
                            list($field, $value) = $comparation;
                            if (isset($this->tabla) && !in_array(strtolower($field), $this->columns[$this->tabla])) {
                                continue;
                            }
                            $field = $this->describe[$this->tabla][strtolower($field)]->getName();
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
                                $where .= $field . " " . $comparator . " ('" . implode("','", $value) . "')";
                            } elseif (is_null($value)) {
                                if (isset($comparation[2]) && is_bool($comparation[2])) {
                                    $comparator = ($comparation[2]) ? ' IS NULL ' : ' IS NOT NULL ';
                                } else {
                                    $comparator = (empty($comparation[2])) ? ' IS NULL ' : $comparation[2];
                                }
                                //$comparator = ($comparator) ? 'IS NULL' : 'IS NOT NULL';
                                $where .= $field . " " . $value . " " . $comparator;
                            } elseif ($value instanceof QueryBuilder) {
                                //$where .= $field . " " . $comparator . " (" . $this->getQuery($value) . ")";
                            } else {
                                if (isset($comparation[2]) && is_bool($comparation[2])) {
                                    $comparator = ($comparation[2]) ? '=' : '!=';
                                } else {
                                    $comparator = (empty($comparation[2])) ? '=' : $comparation[2];
                                }
                                $where .= $this->mountComparation($field . $comparator . $value);
                            }
                        }
                        $where .= " AND ";
                    }
                    $where = substr($where, 0, -5);
                    $where .= ")";
                }
            }
        }
        return $where;
    }

    protected function mountComparation(string $string): string
    {
        $string = trim($string);
        $last_char = substr($string, -1, 1);
        if ($last_char == '"') {
            $string = str_replace('"', "", $string);
        } elseif ($last_char == "'") {
            $string = str_replace("'", "", $string);
        }
        preg_match("/(\w+)(\W+)(\w+)/", $string, $matches);

        list($string, $key, $comparator, $value) = $matches;
        $value = $this->escape($value);
        $key = strtolower($key);
        if (is_array($this->describe) && array_key_exists($key, $this->describe[$this->tabla])) {
            if ((stripos($this->describe[$this->tabla][$key]->getType(), 'char') !== false || stripos($this->describe[$this->tabla][$key]->getType(), 'text') !== false)) {
                $value = "'{$value}'";
            }
            $key = $this->describe[$this->tabla][$key]->getName();
        }

        return "{$key} {$comparator} {$value}";
    }

    protected function mountLimit(int $limit, int $page): string
    {
        return " LIMIT " . (intval($page) * $limit) . "," . $limit;
    }
}

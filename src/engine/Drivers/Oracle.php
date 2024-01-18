<?php

namespace JuanchoSL\Orm\engine\Drivers;

use JuanchoSL\Orm\engine\Cursors\CursorInterface;
use JuanchoSL\Orm\engine\Cursors\OracleCursor;
use JuanchoSL\Orm\engine\Structures\FieldDescription;
use JuanchoSL\Orm\querybuilder\QueryBuilder;
use JuanchoSL\Orm\querybuilder\SQLBuilderTrait;

/**
 * Esta clase permite connect e interactuar con una tabla específica
 * en un servidor ORACLE.
 * 
 * La clase está preparada para realizar las operaciones básicas en una tabla 
 * ORACLE, como insertar registros, actualizarlos, eliminarlos o vaciar una tabla.
 * Permite devolver un array con los nombres de las columnas de la tabla para,
 * por ejemplo, la autoconstrucción de formularios, así como sus claves primarias.
 * 
 * @link http://www.oracle.com/technetwork/topics/winsoft-085727.html
 *
 * @author Juan Sánchez Lecegui
 * @version 1.0.4
 */
class Oracle extends RDBMS implements DbInterface
{

    use SQLBuilderTrait;

    protected $requiredModule = 'oci8';


    public function connect(): void
    {
        //$this->credentials->port = empty($this->credentials->getPort()) ? '1521' : $this->credentials->getPort();
        $this->linkIdentifier = oci_connect($this->credentials->getUsername(), $this->credentials->getPassword(), $this->credentials->getHost() . '/' . $this->credentials->getDataBase(), 'AL32UTF8', OCI_SYSDBA) or throw new \Exception(oci_error()['message']);
    }

    public function disconnect(): bool
    {
        if (!empty($this->linkIdentifier)) {
            $result = oci_close($this->linkIdentifier);
        }
        $this->linkIdentifier = null;
        return $result ?? true;
    }

    public function setTable(string $table = null): void
    {
        parent::setTable(strtoupper($table));
    }
    public function getTables(): array
    {
        return parent::extractTables("SELECT table_name FROM user_tables");
    }

    public function describe(string $tabla = null): array
    {
        if (empty($tabla)) {
            $tabla = $this->tabla;
        }
        if (!empty($tabla)) {
            $result = $this->execute("SELECT column_name \"Field\", nullable \"Null\", concat(concat(concat(data_type,'('),data_length),')') \"Type\", data_default \"Default\" FROM user_tab_columns WHERE table_name='" . strtoupper($tabla) . "'");
            while ($keys = $result->next(self::RESPONSE_ASSOC)) {
                if (empty($keys['Length'])) {
                    preg_match('/([a-zA-Z0-9]+)\W(\d*)/', $keys['Type'], $matches);
                    if (count($matches) >= 2) {
                        $keys['Type'] = $matches[1];
                        $keys['Length'] = $matches[2];
                    }
                }
                $field = new FieldDescription;
                $field
                    ->setName($keys['Field'])
                    ->setType($keys['Type'])
                    ->setLength($keys['Length'])
                    ->setNullable($keys['Null'] != 'N')
                    ->setDefault($keys['Default'])
                    ->setKey(!empty($keys['Default']) && stripos($keys['Default'], 'NEXTVAL'));
                $this->describe[$tabla][strtolower($keys['Field'])] = $field;
            }
            if ($result) {
                $result->free();
            }
        }
        return $this->describe[$tabla] ?? [];
    }
    /*
        public function keys(string $tabla = null): array
        {
            if (empty($tabla)) {
                $tabla = $this->tabla;
            }
            if (!array_key_exists($tabla, $this->describe)) {
                $this->describe($tabla);
            }
            /*
            $this->sqlBuilder = new QueryBuilder;
            $this->sqlBuilder->select(['cols.column_name'])->from('all_constraints cons')->join(['JOIN all_cons_columns cols ON cons.constraint_name = cols.constraint_name AND cons.owner = cols.owner'])->where(array(
                'cons.constraint_type', 'P',
                'cols.table_name', strtoupper($tabla)
            ));
            $cursor = $this->execute($this->sqlBuilder);
            *
            $sql = sprintf("SELECT cols.column_name FROM all_constraints cons JOIN all_cons_columns cols ON cons.constraint_name = cols.constraint_name AND cons.owner = cols.owner WHERE cons.constraint_type='P' AND cols.table_name = '%s'", strtoupper($tabla));
            $cursor = $this->execute($sql);
            while ($keys = $cursor->next(self::RESPONSE_ASSOC)) {
                print_r($keys).PHP_EOL;
                $this->keys[$tabla][] = $keys['COLUMN_NAME'];
            }
            if ($cursor) {
                $cursor->free();
            }
            return $this->keys[$tabla];
        }
    */
    public function execute(QueryBuilder|string $query): CursorInterface
    {
        //        $originalQuery = $query;
//        if ($query instanceof SQLBuilder && $query->operation == SQLBuilder::MODE_SELECT && !empty($query->limit)) {
//            $query->limit = null;
//            $sql = $this->parseQuery($query);
//            $cursor = oci_parse($this->linkIdentifier, $sql);
//            oci_execute($cursor);
//            $this->nResultsPagination = oci_fetch_all($cursor, $res, 0, -1, OCI_FETCHSTATEMENT_BY_ROW);
//        }
        $sql = $this->parseQuery($query);
        //print_r($sql) . PHP_EOL;
        $this->cursor = oci_parse($this->linkIdentifier, $sql);
        if (!oci_execute($this->cursor)) {
            throw new \Exception($sql . ' -> ' . oci_error($this->cursor)['message']);
        }
        return new OracleCursor($this->cursor);
        /*
        try {
        } catch (\Exception $e) {
            print_r($query);
            print_r($sql);
            throw new \Exception($e->getMessage() . ' -> ' . $sql);
        }
        */
    }


    public function affectedRows(): int
    {
        return $this->nResults = oci_num_rows($this->cursor);
    }

    public function lastInsertedId(): int
    {
        $res = $this->execute(sprintf("SELECT max(%s) AS ID FROM %s", $this->keys()[0], strtoupper($this->tabla)));
        $result = $res->next(self::RESPONSE_OBJECT);
        return $this->lastInsertedId = $result->ID;
    }

    protected function parseSelect(QueryBuilder $sqlBuilder): string
    {
        if (!empty($sqlBuilder->limit)) {
            $where = $this->mountWhere($sqlBuilder->condition);
            $join = (count($sqlBuilder->join ?? []) > 0) ? implode(" ", $sqlBuilder->join) : "";
            $inicio = ($sqlBuilder->limit[1] * $sqlBuilder->limit[0]);
            $limit = $sqlBuilder->limit[0];
            $limit += $inicio;
            $inicio++;
            $order = 'ID'; //empty($sqlBuilder->order) ? $this->keys()[0] : $sqlBuilder->order;
            $q = "SELECT * FROM (SELECT t.*, Row_Number() OVER (ORDER BY " . $order . ") MyRow FROM " . strtoupper($sqlBuilder->table) . " t " . $join . " " . $where . ") WHERE MyRow BETWEEN " . $inicio . " AND " . $limit;
            return $q;
        } else {
            return $this->getQuery($sqlBuilder);
        }
    }
    /*
        public function cleanFields(array $camps = array()): array
        {
            $response = array();
            foreach ($camps as $field => $camp) {
                $response[strtoupper($field)] = $camp;
            }
            return parent::cleanFields($response);
        }
    */

    public function truncate(): bool
    {
        parent::truncate();
        $seq = (string) str_replace('."NEXTVAL"', '', $this->describe[$this->tabla][strtolower(current($this->keys[$this->tabla]))]->getDefault());
        $this->execute("ALTER SEQUENCE {$seq} RESTART START WITH 1");
        return true;
    }
    public function drop(): CursorInterface
    {
        $seq = str_replace('."NEXTVAL"', '', $this->describe[$this->tabla][strtolower(current($this->keys[$this->tabla]))]->getDefault());
        parent::drop();
        //print_r(sprintf('DROP SEQUENCE %s', $seq));
        return $this->execute(sprintf('DROP SEQUENCE %s', $seq));
    }

    public function createTable(string $table_name, FieldDescription ...$fields)
    {
        $sql = "CREATE TABLE %s (";
        foreach ($fields as $field) {
            $sql .= "{$field->getName()} " . strtoupper($field->getType());
            if (!$field->isKey()) {
                $sql .="({$field->getLength()})";
                if (!$field->isNullable()) {
                    $sql .= " NOT NULL";
                }
            }
            if(!empty($field->getDefault())){
                $sql .= " DEFAULT {$field->getDefault()}";
            }
            if ($field->isKey()) {
                $sql .= " PRIMARY KEY";
            }
            $sql .= ", ";
        }
        $sql = rtrim($sql ,', ');
        $sql .= ")";
        //print_r(sprintf($sql, strtoupper($table_name)));
        return $this->execute(sprintf($sql, strtoupper($table_name)));
    }
}
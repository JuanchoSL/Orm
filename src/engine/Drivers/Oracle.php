<?php

declare(strict_types=1);

namespace JuanchoSL\Orm\engine\Drivers;

use JuanchoSL\Orm\engine\Cursors\CursorInterface;
use JuanchoSL\Orm\engine\Cursors\OracleCursor;
use JuanchoSL\Orm\engine\Responses\AlterResponse;
use JuanchoSL\Orm\engine\Responses\EmptyResponse;
use JuanchoSL\Orm\engine\Responses\InsertResponse;
use JuanchoSL\Orm\engine\Structures\FieldDescription;
use JuanchoSL\Orm\querybuilder\QueryActionsEnum;
use JuanchoSL\Orm\querybuilder\QueryBuilder;
use JuanchoSL\Orm\querybuilder\SQLBuilderTrait;

class Oracle extends RDBMS implements DbInterface
{

    use SQLBuilderTrait;

    protected $requiredModule = 'oci8';

    public function connect(): void
    {
        //$this->credentials->port = empty($this->credentials->getPort()) ? '1521' : $this->credentials->getPort();
        try {
            $this->linkIdentifier = oci_connect($this->credentials->getUsername(), $this->credentials->getPassword(), $this->credentials->getHost() . '/' . $this->credentials->getDataBase(), 'AL32UTF8', OCI_SYSDBA)
                or throw new \Exception(oci_error()['message']);
        } catch (\Exception $exception) {
            $this->log($exception, 'error', [
                'exception' => $exception,
                'credentials' => $this->credentials
            ]);
            throw $exception;
        }
    }

    public function disconnect(): bool
    {
        if (!empty($this->linkIdentifier)) {
            $result = oci_close($this->linkIdentifier);
        }
        $this->linkIdentifier = null;
        return $result ?? true;
    }

    public function getTables(): array
    {
        return parent::extractTables(QueryBuilder::getInstance()->select(['TABLE_NAME'])->from('user_tables'));
    }

    protected function getParsedField(array $keys): FieldDescription
    {
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
        return $field;
    }

    protected function query(string $query): CursorInterface|InsertResponse|AlterResponse|EmptyResponse
    {
        $cursor = oci_parse($this->linkIdentifier, $query);
        if (!$cursor || !oci_execute($cursor)) {
            $e = new \Exception(oci_error()['message']);
            $this->log($e, 'error', ['exception' => $e, 'query' => $query]);
            throw $e;
        }
        $action = QueryActionsEnum::make(strtoupper(substr($query, 0, strpos($query, ' '))));
        if ($action->isIterable()) {
            $cursor = new OracleCursor($cursor);
            /* } elseif ($action->isInsertable()) {
                 $cursor = new InsertResponse($this->lastInsertedId());*/
        } elseif ($action->isAlterable()) {
            $cursor = new AlterResponse(oci_num_rows($cursor));
        } else {
            $cursor = new EmptyResponse(oci_num_rows($cursor) !== false);
        }
        return $cursor;
    }

    protected function parseDescribe(QueryBuilder $sqlBuilder): string
    {
        return "SELECT column_name \"Field\", nullable \"Null\", concat(concat(concat(data_type,'('),data_length),')') \"Type\", data_default \"Default\" FROM user_tab_columns WHERE table_name='" . strtoupper($sqlBuilder->table) . "'";
    }

    protected function parseSelect(QueryBuilder $sqlBuilder): string
    {
        if (!empty($sqlBuilder->limit)) {
            $where = $this->mountWhere($sqlBuilder->condition, strtolower($sqlBuilder->table));
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

    protected function processInsert(QueryBuilder $builder): InsertResponse
    {
        $this->execute($this->getQuery($builder));
        $builder = QueryBuilder::getInstance()->select(['max(' . $this->keys($builder->table)[0] . ') as ID'])->from($builder->table);
        $res = $this->execute($builder);
        $result = $res->next(static::RESPONSE_OBJECT)->ID;
        $res->free();
        return new InsertResponse($result);
    }

    protected function processTruncate(QueryBuilder $builder): EmptyResponse
    {
        $table = $builder->table;
        $this->keys($table);
        $result = $this->execute($this->getQuery($builder));
        $seq = (string) str_replace('."NEXTVAL"', '', $this->describe[$table][strtolower(current($this->keys[$table]))]->getDefault());
        $this->execute("ALTER SEQUENCE {$seq} RESTART START WITH 1");
        return $result;
    }

    protected function processDrop(QueryBuilder $builder): EmptyResponse
    {
        $table = $builder->table;
        $this->keys($table);
        $result = $this->execute($this->getQuery($builder));
        $seq = (string) str_replace('."NEXTVAL"', '', $this->describe[$table][strtolower(current($this->keys[$table]))]->getDefault());
        $this->execute("DROP SEQUENCE {$seq}");
        return $result;
    }

    protected function processCreate(QueryBuilder $builder)
    {
        $sequence = '';
        $sql = "CREATE TABLE %s (";
        foreach ($builder->values as $field) {
            $sql .= "{$field->getName()} " . strtoupper($field->getType());
            if (!$field->isKey()) {
                $sql .= "({$field->getLength()})";
                if (!$field->isNullable()) {
                    $sql .= " NOT NULL";
                }
                if (!empty($field->getDefault())) {
                    $sql .= " DEFAULT {$field->getDefault()}";
                }
            } else {
                $sequence = "{$builder->table}_" . strtolower($field->getName()) . "_seq";
                $sql .= " DEFAULT {$sequence}.NEXTVAL PRIMARY KEY";
            }
            $sql .= ", ";
        }
        $sql = rtrim($sql, ', ');
        $sql .= ")";
        $this->execute(sprintf("CREATE SEQUENCE %s START WITH 1 INCREMENT BY 1", $sequence));
        return $this->execute(sprintf($sql, strtoupper($builder->table)));
    }
}
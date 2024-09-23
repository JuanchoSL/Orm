<?php

declare(strict_types=1);

namespace JuanchoSL\Orm\Engine\Drivers;

use JuanchoSL\Orm\Engine\Cursors\CursorInterface;
use JuanchoSL\Orm\Engine\DbCredentials;
use JuanchoSL\Orm\Engine\Responses\AlterResponse;
use JuanchoSL\Orm\Engine\Responses\EmptyResponse;
use JuanchoSL\Orm\Engine\Responses\InsertResponse;
use JuanchoSL\Orm\Engine\Structures\FieldDescription;
use JuanchoSL\Orm\Querybuilder\QueryActionsEnum;
use JuanchoSL\Orm\Querybuilder\QueryBuilder;
use JuanchoSL\Orm\Querybuilder\Types\AbstractQueryBuilder;
use Psr\Log\LoggerAwareTrait;

abstract class RDBMS implements DbInterface
{
    use LoggerAwareTrait;

    const RESPONSE_OBJECT = 'object';
    const RESPONSE_ASSOC = 'assoc';
    const RESPONSE_ROWS = 'rows';

    protected $linkIdentifier;
    protected $describe = [];
    protected $columns = [];
    protected $keys = [];
    protected DbCredentials $credentials;
    protected bool $debug = false;
    function __construct(DbCredentials $credentials)
    {
        $this->credentials = $credentials;
    }
    abstract protected function getQuery(AbstractQueryBuilder|QueryBuilder $queryBuilder): string;

    abstract protected function getParsedField(array $keys): FieldDescription;

    abstract protected function query(string $query): CursorInterface|InsertResponse|AlterResponse|EmptyResponse;

    public function setDebug(bool $debug = false): void
    {
        $this->debug = $debug;
    }

    protected function log(\Stringable|string $message, $log_level, $context = [])
    {
        if (isset($this->logger)) {
            if ($this->debug || $log_level != 'debug') {
                $context['memory'] = memory_get_usage();
                $context['Engine'] = (new \ReflectionClass($this))->getShortName();
                $this->logger->log($log_level, $message, $context);
            }
        }
    }

    protected function setTable(string $tabla): static
    {
        if (!array_key_exists($tabla, $this->describe)) {
            $this->describe($tabla);
            $this->columns($tabla);
            $this->keys($tabla);
        }
        return $this;
    }

    public function describe(string $tabla): array
    {
        $describe = [];
        $fields = [];
        $result = $this->execute(QueryBuilder::getInstance()->doAction(QueryActionsEnum::DESCRIBE)->table($tabla));
        while ($keys = $result->next(static::RESPONSE_ASSOC)) {
            $fields[] = $keys;
            $field = $this->getParsedField($keys);
            $describe[strtolower($field->getName())] = $field;
        }
        $result->free();
        $this->describe[strtolower($tabla)] = $describe;
        $this->log("Describe {table}", 'debug', ['table' => $tabla, 'response' => $fields, 'fields' => $describe]);
        unset($fields);
        unset($describe);
        return $this->describe[$tabla];
    }

    public function columns(string $tabla): array
    {
        if (!array_key_exists($tabla, $this->describe)) {
            $this->describe($tabla);
        }
        return $this->columns[$tabla] = array_keys($this->describe[$tabla]);
    }

    public function keys(string $tabla): array
    {
        if (!array_key_exists($tabla, $this->describe)) {
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
        $this->disconnect();
    }

    /**
     * Devuelve el listado de nombres de las tablas del servidor y esquema seleccionado
     * @return mixed Array cuyo contenido es el listado de nombres de las tablas del esquema
     */
    protected function extractTables($sql): array
    {
        $taules = array();
        $result = $this->execute($sql);
        while ($linea = $result->next(self::RESPONSE_ROWS)) {
            $taules[] = strtolower($linea[0]);
        }
        $result->free();
        return $taules;
    }

    public function escape(string $str): string
    {
        return str_replace(["'", '"'], ["''", '""'], $str);

        if (!empty($str) && is_string($str)) {
            $str = stripslashes($str);
            return (string) str_replace(array('\\', "\0", "\n", "\r", "'", '"', "\x1a"), array('\\\\', '\\0', '\\n', '\\r', "\'", '\\"', '\\Z'), $str);
        }
    }

    public function execute(AbstractQueryBuilder|QueryBuilder|string $query): CursorInterface|InsertResponse|AlterResponse|EmptyResponse
    {
        if (!$this->linkIdentifier) {
            $this->connect();
            $this->log("Connected", 'debug', ['function' => __FUNCTION__]);
        }
        if (is_object($query)) {
            if (!is_null($query->operation)) {
                if (method_exists($this, 'process' . ucfirst(strtolower($query->operation->value)))) {
                    return call_user_func(array($this, 'process' . ucfirst(strtolower($query->operation->value))), $query);
                } elseif (!is_null($query->operation) && method_exists($this, 'parse' . ucfirst(strtolower($query->operation->value)))) {
                    $query = call_user_func(array($this, 'parse' . ucfirst(strtolower($query->operation->value))), $query);
                }
            }
            if (is_object($query)) {
                $query = $this->getQuery($query);
            }
        }
        $micro_time = microtime(true);
        try {
            $cursor = $this->query($query);
            $this->log('{query}', 'debug', ['query' => $query, 'results' => $cursor->count(), 'time' => microtime(true) - $micro_time]);
        } catch (\Exception $exception) {
            $this->log($exception, 'error', ['exception' => $exception, "query" => $query]);
            throw $exception;
        }
        return $cursor;
    }

    protected function cleanFields(string $table, array $camps = array())
    {
        $this->log(__FUNCTION__, 'debug', ['params' => func_get_args()]);
        $this->columns($table);
        foreach ($camps as $key => $value) {
            if (!isset($this->columns[$table]) or !in_array(strtolower($key), $this->columns[$table])) {
                unset($camps[$key]);
            } else {
                if (is_string($value)) {
                    $value = $this->escape($value);
                }
                $field = $this->describe[$table][strtolower($key)]->getName();
                unset($camps[$key]);
                $camps[$field] = $value;
            }
        }
        return $camps;
    }

    public function __sleep()
    {
        return ['credentials', 'logger', 'debug'];
    }

    public function __wakeup()
    {
        $this->connect();
    }
}

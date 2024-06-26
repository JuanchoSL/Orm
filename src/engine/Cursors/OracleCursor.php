<?php

namespace JuanchoSL\Orm\engine\Cursors;

use JuanchoSL\Orm\engine\Drivers\RDBMS;

class OracleCursor extends AbstractCursor implements CursorInterface
{
    protected $data = [];
    public function next($typeReturn = null)
    {
        if (is_bool($this->cursor))
            return false;
        if (!empty ($this->data)) {
            $val = current($this->data);
            if (empty ($val)) {
                return false;
            }
            next($this->data);
            switch ($typeReturn) {
                case RDBMS::RESPONSE_ROWS:
                    return array_values((array) $val);

                case RDBMS::RESPONSE_ASSOC:
                    return (array) $val;

                case RDBMS::RESPONSE_OBJECT:
                default:
                    return $val;
            }
        } else {
            switch ($typeReturn) {
                case RDBMS::RESPONSE_ROWS:
                    return oci_fetch_row($this->cursor);

                case RDBMS::RESPONSE_ASSOC:
                    return oci_fetch_assoc($this->cursor);

                case RDBMS::RESPONSE_OBJECT:
                default:
                    return oci_fetch_object($this->cursor);
            }
        }
    }

    public function get(): array
    {
        if (empty ($this->data)) {
            $this->data = parent::get();
            reset($this->data);
        }
        return $this->data;
    }
    public function count(): int
    {
        if (oci_statement_type($this->cursor) != "SELECT") {
            return oci_num_rows($this->cursor);
        }
        if (empty ($this->data)) {
            $this->get();
        }
        return count($this->data);
    }

    public function free(): bool
    {
        if (!empty ($this->cursor)) {
            //return @oci_free_statement($this->cursor);
        }
        return true;
    }
}
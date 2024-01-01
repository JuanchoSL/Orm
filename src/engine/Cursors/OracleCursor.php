<?php

namespace JuanchoSL\Orm\engine\Cursors;

use JuanchoSL\Orm\engine\Drivers\RDBMS;

class OracleCursor extends AbstractCursor implements CursorInterface
{
    protected $data = [];
    public function next($typeReturn = null)
    {
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

    public function get(): array
    {
        if (empty($this->data)) {
            $this->data = parent::get();
        }
        return $this->data;
    }
    public function count(): int
    {
        if(empty($this->data)){
            $this->get();
        }
        return oci_num_rows($this->cursor);

        $i = 0;
        while ($this->next()) {
            $i++;
        }
        return $i;
    }

    public function free(): bool
    {
        return oci_free_statement($this->cursor);
    }
}
<?php

namespace JuanchoSL\Orm\engine\Cursors;

use JuanchoSL\Orm\engine\Drivers\RDBMS;

class Db2Cursor extends AbstractCursor implements CursorInterface
{

    protected $data = [];
    public function next($typeReturn = null)
    {
        switch ($typeReturn) {
            case RDBMS::RESPONSE_ROWS:
                return db2_fetch_array($this->cursor);

            case RDBMS::RESPONSE_ASSOC:
                return db2_fetch_assoc($this->cursor);

            case RDBMS::RESPONSE_OBJECT:
            default:
                return db2_fetch_object($this->cursor);
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
        if (empty ($this->data)) {
            $this->get();
        }
        return count($this->data);
    }

    public function free(): bool
    {
        return db2_free_result($this->cursor);
    }
}
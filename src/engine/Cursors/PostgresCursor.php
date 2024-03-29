<?php

namespace JuanchoSL\Orm\engine\Cursors;

use JuanchoSL\Orm\engine\Drivers\RDBMS;

class PostgresCursor extends AbstractCursor implements CursorInterface
{

    public function next($typeReturn = null)
    {
        switch ($typeReturn) {
            case RDBMS::RESPONSE_ROWS:
                return pg_fetch_row($this->cursor);

            case RDBMS::RESPONSE_ASSOC:
                return pg_fetch_assoc($this->cursor);

            case RDBMS::RESPONSE_OBJECT:
            default:
                return pg_fetch_object($this->cursor);
        }
    }

    public function count(): int
    {
        return pg_num_rows($this->cursor);
    }

    public function affected(): int
    {
        return pg_affected_rows($this->cursor);
    }

    public function free(): bool
    {
        return pg_free_result($this->cursor);
    }
}
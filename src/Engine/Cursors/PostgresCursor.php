<?php

declare(strict_types=1);

namespace JuanchoSL\Orm\Engine\Cursors;

use JuanchoSL\Orm\Engine\Drivers\RDBMS;

class PostgresCursor extends AbstractCursor implements CursorInterface
{

    public function next($typeReturn = null)
    {
        if (is_bool($this->cursor))
            return false;
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

    public function free(): bool
    {
        return pg_free_result($this->cursor);
    }
}
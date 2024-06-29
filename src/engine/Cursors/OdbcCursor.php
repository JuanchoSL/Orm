<?php

declare(strict_types=1);

namespace JuanchoSL\Orm\engine\Cursors;

use JuanchoSL\Orm\engine\Drivers\RDBMS;

class OdbcCursor extends AbstractCursor implements CursorInterface
{

    public function next($typeReturn = null)
    {
        switch ($typeReturn) {
            case RDBMS::RESPONSE_ROWS:
                return odbc_fetch_row($this->cursor);

            case RDBMS::RESPONSE_ASSOC:
                return odbc_fetch_array($this->cursor);

            case RDBMS::RESPONSE_OBJECT:
            default:
                return odbc_fetch_object($this->cursor);
        }
    }

    public function count(): int
    {
        return odbc_num_rows($this->cursor);
    }

    public function free(): bool
    {
        return odbc_free_result($this->cursor);
    }
}
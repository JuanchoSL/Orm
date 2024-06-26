<?php

namespace JuanchoSL\Orm\engine\Cursors;

use JuanchoSL\Orm\engine\Drivers\RDBMS;

class SqlsrvCursor extends AbstractCursor implements CursorInterface
{

    public function next($typeReturn = null)
    {
        switch ($typeReturn) {
            case RDBMS::RESPONSE_ROWS:
                return sqlsrv_fetch_array($this->cursor, SQLSRV_FETCH_NUMERIC) ?? false;

            case RDBMS::RESPONSE_ASSOC:
                return sqlsrv_fetch_array($this->cursor, SQLSRV_FETCH_ASSOC) ?? false;

            case RDBMS::RESPONSE_OBJECT:
            default:
                return sqlsrv_fetch_object($this->cursor) ?? false;
        }
    }

    public function count(): int
    {
        return sqlsrv_num_rows($this->cursor);
    }

    public function free(): bool
    {
        return sqlsrv_free_stmt($this->cursor);
    }
}
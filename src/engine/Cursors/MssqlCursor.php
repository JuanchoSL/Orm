<?php

namespace JuanchoSL\Orm\engine\Cursors;

use JuanchoSL\Orm\engine\Drivers\RDBMS;

class MssqlCursor extends AbstractCursor implements CursorInterface
{

    public function next($typeReturn = null)
    {
        switch ($typeReturn) {
            case RDBMS::RESPONSE_ROWS:
                return mssql_fetch_row($this->cursor);

            case RDBMS::RESPONSE_ASSOC:
                return mssql_fetch_assoc($this->cursor);

            case RDBMS::RESPONSE_OBJECT:
            default:
                return mssql_fetch_object($this->cursor);
        }
    }

    public function count():int
    {
        return mssql_num_rows($this->cursor);
    }

    public function free():bool
    {
        return mssql_free_result($this->cursor);
    }
}
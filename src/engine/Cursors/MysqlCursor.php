<?php

namespace JuanchoSL\Orm\engine\Cursors;

use JuanchoSL\Orm\engine\Drivers\RDBMS;

class MysqlCursor extends AbstractCursor implements CursorInterface
{

    public function next($typeReturn = null)
    {
        switch ($typeReturn) {
            case RDBMS::RESPONSE_ROWS:
                return mysqli_fetch_row($this->cursor);

            case RDBMS::RESPONSE_ASSOC:
                return mysqli_fetch_assoc($this->cursor);

            case RDBMS::RESPONSE_OBJECT:
            default:
                return mysqli_fetch_object($this->cursor);
        }
    }

    public function count(): int
    {
        return mysqli_num_rows($this->cursor);
    }

    public function free(): bool
    {
        mysqli_free_result($this->cursor);
        return true;
    }
}
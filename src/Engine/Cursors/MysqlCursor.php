<?php

declare(strict_types=1);

namespace JuanchoSL\Orm\Engine\Cursors;

use JuanchoSL\Orm\Engine\Drivers\RDBMS;

class MysqlCursor extends AbstractCursor implements CursorInterface
{

    public function next($typeReturn = null)
    {
        switch ($typeReturn) {
            case RDBMS::RESPONSE_ROWS:
                return mysqli_fetch_row($this->cursor) ?? false;

            case RDBMS::RESPONSE_ASSOC:
                return mysqli_fetch_assoc($this->cursor) ?? false;

            case RDBMS::RESPONSE_OBJECT:
            default:
                return mysqli_fetch_object($this->cursor) ?? false;
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
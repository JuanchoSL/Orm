<?php

namespace JuanchoSL\Orm\engine\Cursors;

use JuanchoSL\Orm\engine\Drivers\RDBMS;

class Db2Cursor extends AbstractCursor implements CursorInterface
{

    public function next($typeReturn = null)
    {
        switch ($typeReturn) {
            case RDBMS::RESPONSE_ROWS:
                return db2_fetch_row($this->cursor);

            case RDBMS::RESPONSE_ASSOC:
                return db2_fetch_assoc($this->cursor);

            case RDBMS::RESPONSE_OBJECT:
            default:
                return db2_fetch_object($this->cursor);
        }
    }

    public function count():int
    {
        return db2_num_rows($this->cursor);
    }

    public function free():bool
    {
        return db2_free_result($this->cursor);
    }
}
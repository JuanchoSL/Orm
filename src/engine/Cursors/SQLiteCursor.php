<?php

namespace JuanchoSL\Orm\engine\Cursors;

use JuanchoSL\Orm\engine\Drivers\RDBMS;

class SQLiteCursor extends AbstractCursor implements CursorInterface
{

    public function next($typeReturn = null)
    {
        switch ($typeReturn) {
            case RDBMS::RESPONSE_ROWS:
                return $this->cursor->fetchArray(SQLITE3_NUM);

            case RDBMS::RESPONSE_ASSOC:
                return $this->cursor->fetchArray(SQLITE3_ASSOC);

            case RDBMS::RESPONSE_OBJECT:
            default:
                return json_decode(json_encode($this->cursor->fetchArray(SQLITE3_ASSOC)), false);
        }
    }

    public function count(): int
    {
        $nResults = 0;
        while ($this->next() !== false) {
            $nResults++;
        }
        $this->cursor->reset();
        return $nResults;
    }

    public function free(): bool
    {
        return true;
        //@call_user_func([$this->cursor, 'finalize']);
        //            $this->cursor->finalize();
    }
}
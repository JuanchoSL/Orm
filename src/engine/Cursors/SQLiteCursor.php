<?php

declare(strict_types=1);

namespace JuanchoSL\Orm\engine\Cursors;

use JuanchoSL\Orm\engine\Drivers\RDBMS;

class SQLiteCursor extends AbstractCursor implements CursorInterface
{

    public function next($typeReturn = null)
    {
        if (is_bool($this->cursor))
            return false;
        switch ($typeReturn) {
            case RDBMS::RESPONSE_ROWS:
                return $this->cursor->fetchArray(SQLITE3_NUM);

            case RDBMS::RESPONSE_ASSOC:
                return $this->cursor->fetchArray(SQLITE3_ASSOC);

            case RDBMS::RESPONSE_OBJECT:
            default:
                $value = $this->cursor->fetchArray(SQLITE3_ASSOC);
                return (empty($value)) ? false : json_decode(json_encode($value), false);
        }
    }

    public function count(): int
    {
        $nResults = 0;
        if (is_object($this->cursor)) {
            while ($this->next() !== false) {
                $nResults++;
            }
            $this->cursor->reset();
        }
        return $nResults;
    }

    public function free(): bool
    {
        return true;
        //@call_user_func([$this->cursor, 'finalize']);
        //            $this->cursor->finalize();
    }
}
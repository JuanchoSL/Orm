<?php

declare(strict_types=1);

namespace JuanchoSL\Orm\Engine\Cursors;

use JuanchoSL\Orm\Engine\Drivers\RDBMS;

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
        try {
            return $this->cursor->finalize();
        } catch (\Exception $e) {
        }

        return true;
        //@call_user_func([$this->cursor, 'finalize']);
    }
}
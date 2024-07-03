<?php

declare(strict_types=1);

namespace JuanchoSL\Orm\Querybuilder;

enum QueryActionsEnum: string
{
    case SELECT = "SELECT";
    case INSERT = "INSERT";
    case UPDATE = "UPDATE";
    case DELETE = "DELETE";
    case TRUNCATE = "TRUNCATE";
    case DROP = "DROP";
    case DESCRIBE = "DESCRIBE";
    case EXEC = "EXEC";
    case PRAGMA = "PRAGMA";
    case SHOW = "SHOW";
    case ALTER = "ALTER";
    case CREATE = "CREATE";

    public function isIterable()
    {
        return match ($this) {
            static::SELECT, static::DESCRIBE, static::PRAGMA, static::EXEC, static::SHOW => true,
            default => false
        };
    }
    public function isAlterable()
    {
        return match ($this) {
            static::UPDATE, static::DELETE => true,
            default => false
        };
    }
    public function isInsertable()
    {
        return match ($this) {
            static::INSERT => true,
            default => false
        };
    }
    public function isEmpty()
    {
        return match ($this) {
            static::TRUNCATE, static::CREATE, static::DROP => true,
            default => false
        };
    }

    public static function make(string $action)
    {
        foreach (QueryActionsEnum::cases() as $case) {
            if (strtoupper($action) == $case->value) {
                return $case;
            }
        }

    }
}
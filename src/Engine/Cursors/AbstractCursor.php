<?php

declare(strict_types=1);

namespace JuanchoSL\Orm\Engine\Cursors;

abstract class AbstractCursor
{
    protected $cursor;
    public function __construct($cursor)
    {
        $this->cursor = $cursor;
    }

    abstract function next();
    abstract function free();

    public function get(): array
    {
        $return = array();
        while (($row = $this->next()) !== false) {
            $return[] = $row;
        }
        //$this->free();
        return $return;
    }

    public function __destruct()
    {
        //$this->free();
    }
}
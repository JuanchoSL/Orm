<?php

namespace JuanchoSL\Orm\engine\Cursors;

abstract class AbstractCursor
{
    protected $cursor;
    public function __construct($cursor)
    {
        $this->cursor = $cursor;
    }

    abstract function next();

    public function get(): array
    {
        $return = array();
        while ($row = $this->next()) {
            $return[] = $row;
        }

        return $return;
    }
}
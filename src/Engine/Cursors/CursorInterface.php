<?php

namespace JuanchoSL\Orm\Engine\Cursors;

interface CursorInterface
{
    public function next($type_return = null);

    public function get(): array;
    public function count(): int;
    public function free(): bool;
}
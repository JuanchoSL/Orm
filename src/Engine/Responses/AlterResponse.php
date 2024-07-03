<?php

declare(strict_types=1);

namespace JuanchoSL\Orm\Engine\Responses;

class AlterResponse implements \Stringable, \Countable
{
    private $data;
    public function __construct($data)
    {
        $this->data = $data;
    }

    public function count(): int
    {
        return $this->data;
    }
    
    public function __toString(): string
    {
        return (string)$this->data;
    }
}
<?php

namespace JuanchoSL\Orm\engine\Structures;

class FieldDescription
{
    const FIELD_TYPE_INTEGER = 'int';
    const FIELD_TYPE_FLOAT = 'float';
    const FIELD_TYPE_TEXT = 'text';
    const FIELD_TYPE_BLOB = 'blob';
    private $field_name;
    private $field_type;
    private $field_length;
    private $field_nullable;
    private $field_default;
    private $field_is_key;

    public function getName(): string
    {
        return $this->field_name;
    }
    public function setName(string $field_name)
    {
        $this->field_name = $field_name;
        return $this;
    }
    public function getType(): string
    {
        return $this->field_type;
    }
    public function setType(string $field_type)
    {
        $this->field_type = $field_type;
        return $this;
    }
    public function getLength()
    {
        return $this->field_length;
    }
    public function setLength($field_length)
    {
        $this->field_length = $field_length;
        return $this;
    }
    public function getNullable(): bool
    {
        return $this->field_nullable;
    }
    public function setNullable(bool $field_nullable)
    {
        $this->field_nullable = $field_nullable;
        return $this;
    }
    public function getDefault(): ?string
    {
        return $this->field_default;
    }
    public function setDefault(?string $field_default)
    {
        $this->field_default = $field_default;
        return $this;
    }
    public function getKey(): ?string
    {
        return $this->field_is_key;
    }
    public function setKey(?string $field_key)
    {
        $this->field_is_key = $field_key;
        return $this;
    }

    public function isKey()
    {
        return !empty($this->field_is_key);
    }

    public function isNullable()
    {
        return $this->field_nullable;
    }
}
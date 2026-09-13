<?php

namespace App\Factories;

abstract class Field
{
    public string $name;

    public FieldType $type;

    public bool $required;

    public int $order;

    public function __construct(string $name, FieldType $type, bool $required, int $order)
    {
        $this->name = $name;
        $this->type = $type;
        $this->required = $required;
        $this->order = $order;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'type' => $this->type->value,
            'required' => $this->required,
            'order' => $this->order,
            ...$this->extraAttributes(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function extraAttributes(): array
    {
        return [];
    }
}

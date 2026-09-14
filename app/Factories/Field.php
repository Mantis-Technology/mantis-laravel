<?php

namespace App\Factories;

abstract class Field
{
    public string $name;

    public string $label;

    public FieldType $type;

    public bool $required;

    public int $order;

    public ?string $placeholder;

    public function __construct(
        string $name,
        string $label,
        FieldType $type,
        bool $required,
        int $order,
        ?string $placeholder = null
    ) {
        $this->name = $name;
        $this->label = $label;
        $this->type = $type;
        $this->required = $required;
        $this->order = $order;
        $this->placeholder = $placeholder;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'label' => $this->label,
            'type' => $this->type->value,
            'required' => $this->required,
            'order' => $this->order,
            ...($this->placeholder !== null ? ['placeholder' => $this->placeholder] : []),
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

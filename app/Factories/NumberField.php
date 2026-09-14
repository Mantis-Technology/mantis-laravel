<?php

namespace App\Factories;

class NumberField extends Field
{
    public int $min;

    public int $max;

    public function __construct(string $name, string $label, bool $required, int $order, ?string $placeholder, int $min, int $max)
    {
        parent::__construct($name, $label, FieldType::NUMBER, $required, $order, $placeholder);
        $this->min = $min;
        $this->max = $max;
    }

    protected function extraAttributes(): array
    {
        return [
            'min' => $this->min,
            'max' => $this->max,
        ];
    }
}

<?php

namespace App\Factories;

class NumberField extends Field
{
    public int $minValue;

    public int $maxValue;

    public function __construct(string $name, bool $required, int $order, int $minValue, int $maxValue)
    {
        parent::__construct($name, FieldType::NUMBER, $required, $order);
        $this->minValue = $minValue;
        $this->maxValue = $maxValue;
    }

    protected function extraAttributes(): array
    {
        return [
            'minValue' => $this->minValue,
            'maxValue' => $this->maxValue,
        ];
    }
}

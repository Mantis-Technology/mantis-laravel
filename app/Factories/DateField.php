<?php

namespace App\Factories;

class DateField extends Field
{
    public function __construct(string $name, bool $required, int $order)
    {
        parent::__construct($name, FieldType::DATE, $required, $order);
    }
}

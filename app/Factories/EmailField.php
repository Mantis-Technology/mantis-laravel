<?php

namespace App\Factories;

class EmailField extends Field
{
    public function __construct(string $name, bool $required, int $order)
    {
        parent::__construct($name, FieldType::EMAIL, $required, $order);
    }
}

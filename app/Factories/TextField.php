<?php

namespace App\Factories;

class TextField extends Field
{
    public function __construct(string $name, bool $required, int $order)
    {
        parent::__construct($name, FieldType::TEXT, $required, $order);
    }
}

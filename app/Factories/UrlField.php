<?php

namespace App\Factories;

class UrlField extends Field
{
    public function __construct(string $name, bool $required, int $order)
    {
        parent::__construct($name, FieldType::URL, $required, $order);
    }
}

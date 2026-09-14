<?php

namespace App\Dto;

class TemplateSectionOrder
{
    public int $value;

    public function __construct(int $value)
    {
        if ($value < 1) {
            throw new \InvalidArgumentException('Order must be a positive integer.');
        }
        $this->value = $value;
    }
}

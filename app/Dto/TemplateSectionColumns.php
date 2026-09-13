<?php

namespace App\Dto;

class TemplateSectionColumns
{
    public int $value;

    public function __construct(int $value)
    {
        if ($value < 1 || $value > 12) {
            throw new \InvalidArgumentException('Columns must be between 1 and 12.');
        }
        $this->value = $value;
    }
}

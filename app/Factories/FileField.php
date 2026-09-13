<?php

namespace App\Factories;

class FileField extends Field
{
    /**
     * @var array<int, string>
     */
    public array $allowedMimeTypes;

    public int $maxFileSize;

    /**
     * @param  array<int, string>  $allowedMimeTypes
     */
    public function __construct(string $name, bool $required, int $order, array $allowedMimeTypes, int $maxFileSize)
    {
        parent::__construct($name, FieldType::FILE, $required, $order);
        $this->allowedMimeTypes = $allowedMimeTypes;
        $this->maxFileSize = $maxFileSize;
    }

    protected function extraAttributes(): array
    {
        return [
            'allowedMimeTypes' => $this->allowedMimeTypes,
            'maxFileSize' => $this->maxFileSize,
        ];
    }
}

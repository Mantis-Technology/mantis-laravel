<?php

namespace App\Factories;

class FileField extends Field
{
    /**
     * @var array<int, string>
     */
    public array $mimeTypes;

    public int $maxFileSize;

    /**
     * @param  array<int, string>  $mimeTypes
     */
    public function __construct(string $name, string $label, bool $required, int $order, ?string $placeholder, array $mimeTypes, int $maxFileSize)
    {
        parent::__construct($name, $label, FieldType::FILE, $required, $order, $placeholder);
        $this->mimeTypes = $mimeTypes;
        $this->maxFileSize = $maxFileSize;
    }

    protected function extraAttributes(): array
    {
        return [
            'mimeTypes' => $this->mimeTypes,
            'maxFileSize' => $this->maxFileSize,
        ];
    }
}

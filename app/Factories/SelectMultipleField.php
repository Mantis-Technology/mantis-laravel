<?php

namespace App\Factories;

class SelectMultipleField extends Field
{
    /**
     * @var array<int, array{value: string, label: string}>
     */
    public array $options;

    /**
     * @param  array<int, array{value: string, label: string}>  $options
     */
    public function __construct(string $name, string $label, bool $required, int $order, ?string $placeholder, array $options)
    {
        parent::__construct($name, $label, FieldType::SELECT_MULTIPLE, $required, $order, $placeholder);
        $this->options = $options;
    }

    protected function extraAttributes(): array
    {
        return ['options' => $this->options];
    }
}

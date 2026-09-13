<?php

namespace App\Factories;

class RadioField extends Field
{
    /**
     * @var array<int, string>
     */
    public array $options;

    /**
     * @param  array<int, string>  $options
     */
    public function __construct(string $name, bool $required, int $order, array $options)
    {
        parent::__construct($name, FieldType::RADIO, $required, $order);
        $this->options = $options;
    }

    protected function extraAttributes(): array
    {
        return ['options' => $this->options];
    }
}

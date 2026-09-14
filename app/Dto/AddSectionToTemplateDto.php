<?php

namespace App\Dto;

use App\Factories\Field;

class AddSectionToTemplateDto
{
    public string $name;

    public string $description;

    public TemplateSectionOrder $order;

    public TemplateSectionColumns $columns;

    /**
     * @var Field[] Lista explícita de objetos Field
     */
    public array $fields;

    /**
     * @param  Field[]  $fields
     */
    public function __construct(
        string $name,
        string $description,
        TemplateSectionOrder $order,
        TemplateSectionColumns $columns,
        array $fields
    ) {
        $this->name = $name;
        $this->description = $description;
        $this->order = $order;
        $this->columns = $columns;
        $this->fields = $fields;
    }
}

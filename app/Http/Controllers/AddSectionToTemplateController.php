<?php

namespace App\Http\Controllers;

use App\Dto\AddSectionToTemplateDto;
use App\Dto\TemplateSectionColumns;
use App\Dto\TemplateSectionOrder;
use App\Factories\FieldFactory;
use App\Factories\FieldType;
use App\Services\AddSectionToTemplate;
use Illuminate\Http\Request;

class AddSectionToTemplateController extends Controller
{
    public function __construct(
        private AddSectionToTemplate $addSectionService
    ) {}

    public function __invoke(Request $req, int $templateId, int $version): void
    {
        $fields = array_map(function (array $fieldData) {
            return FieldFactory::createField(
                name: $fieldData['name'],
                type: FieldType::from($fieldData['type']),
                required: (bool) $fieldData['required'],
                order: (int) $fieldData['order'],
                config: $fieldData
            );
        }, $req->input('fields', []));

        $dto = new AddSectionToTemplateDto(
            name: $req->input('name', ''),
            description: $req->input('description', ''),
            order: new TemplateSectionOrder((int) $req->input('order', 1)),
            columns: new TemplateSectionColumns((int) $req->input('columns', 12)),
            fields: $fields
        );

        $this->addSectionService->execute($templateId, $version, $dto);
    }
}

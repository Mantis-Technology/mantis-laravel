<?php

declare(strict_types=1);

namespace App\Http\Controllers\AssetCardTemplates;

use App\Factories\FieldType;
use App\Http\Controllers\Controller;
use App\Models\AssetCardTemplate;
use App\Services\SyncAssetCardTemplateSections;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UpdateAssetCardTemplateSectionsController extends Controller
{
    public function __construct(
        private SyncAssetCardTemplateSections $syncSections
    ) {}

    public function __invoke(Request $request, int $templateId, int $version): RedirectResponse
    {
        $template = AssetCardTemplate::query()->findOrFail($templateId);

        if ($template->versions()->where('version', $version)->doesntExist()) {
            abort(404);
        }

        $validated = $request->validate([
            'sections' => ['present', 'array'],
            'sections.*.id' => ['nullable', 'string'],
            'sections.*.name' => ['required', 'string', 'max:255'],
            'sections.*.description' => ['nullable', 'string'],
            'sections.*.columns' => ['required', 'integer', 'between:1,12'],
            'sections.*.fields' => ['present', 'array'],
            'sections.*.fields.*.name' => ['required', 'string', 'max:255'],
            'sections.*.fields.*.label' => ['required', 'string', 'max:255'],
            'sections.*.fields.*.type' => [
                'required',
                'string',
                Rule::in(array_map(static fn (FieldType $type): string => $type->value, FieldType::cases())),
            ],
            'sections.*.fields.*.required' => ['required', 'boolean'],
            'sections.*.fields.*.order' => ['nullable', 'integer'],
            'sections.*.fields.*.placeholder' => ['nullable', 'string'],
            'sections.*.fields.*.options' => ['nullable', 'array'],
            'sections.*.fields.*.options.*.value' => ['nullable', 'string'],
            'sections.*.fields.*.options.*.label' => ['nullable', 'string'],
            'sections.*.fields.*.min' => ['nullable', 'integer'],
            'sections.*.fields.*.max' => ['nullable', 'integer'],
            'sections.*.fields.*.mimeTypes' => ['nullable', 'array'],
            'sections.*.fields.*.mimeTypes.*' => ['string'],
            'sections.*.fields.*.maxFileSize' => ['nullable', 'integer'],
        ]);

        $this->syncSections->execute($template, $version, $validated['sections']);

        return back()->with('success', 'Secciones actualizadas correctamente.');
    }
}

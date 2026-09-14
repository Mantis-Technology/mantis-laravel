<?php

declare(strict_types=1);

namespace App\Http\Controllers\AssetCards;

use App\Http\Controllers\Controller;
use App\Models\AssetCard;
use App\Models\AssetCardTemplate;
use App\Services\AssetCardRules;
use App\Services\BuildAssetCardData;
use App\Services\TemplateSections;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AssetCardController extends Controller
{
    public function __construct(
        private TemplateSections $templateSections,
        private AssetCardRules $rules,
        private BuildAssetCardData $buildData
    ) {}

    public function index(): Response
    {
        $assetCards = AssetCard::query()
            ->with('template')
            ->orderByDesc('updated_at')
            ->get()
            ->map(fn (AssetCard $assetCard): array => [
                'id' => $assetCard->id,
                'code' => $assetCard->code,
                'template_name' => $assetCard->template?->name,
                'version' => $assetCard->version,
                'updated_at' => $assetCard->updated_at?->toDateTimeString(),
                'show_url' => route('asset-cards.show', $assetCard),
                'edit_url' => route('asset-cards.edit', $assetCard),
                'destroy_url' => route('asset-cards.destroy', $assetCard),
            ])
            ->values()
            ->all();

        return Inertia::render('AssetCards/index', [
            'assetCards' => $assetCards,
            'createUrl' => route('asset-cards.create'),
        ]);
    }

    public function create(Request $request): Response
    {
        $templateId = $request->integer('template_id');
        $version = $request->integer('version');
        $selectedTemplate = null;
        $sections = [];

        if ($templateId > 0 && $version > 0) {
            $template = AssetCardTemplate::query()->find($templateId);

            if (
                $template instanceof AssetCardTemplate &&
                $template->versions()->where('version', $version)->exists()
            ) {
                $selectedTemplate = [
                    'id' => $template->id,
                    'name' => $template->name,
                    'version' => $version,
                ];
                $sections = $this->templateSections->forVersion($template, $version);
            }
        }

        return Inertia::render('AssetCards/Create/index', [
            'templates' => $this->templatesWithVersions(),
            'selectedTemplate' => $selectedTemplate,
            'sections' => $sections,
            'createUrl' => route('asset-cards.create'),
            'action' => route('asset-cards.store'),
            'cancelUrl' => route('asset-cards.index'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'template_id' => ['required', 'integer', Rule::exists('asset_card_templates', 'id')],
            'version' => ['required', 'integer'],
            'code' => ['required', 'string', 'max:255', Rule::unique('asset_cards', 'code')],
            'values' => ['array'],
        ]);

        $template = AssetCardTemplate::query()->findOrFail((int) $validated['template_id']);
        $version = (int) $validated['version'];

        if ($template->versions()->where('version', $version)->doesntExist()) {
            abort(404);
        }

        $sections = $this->templateSections->forVersion($template, $version);
        $request->validate($this->rules->forSections($sections));

        $assetCard = AssetCard::query()->create([
            'asset_card_template_id' => $template->id,
            'version' => $version,
            'code' => (string) $validated['code'],
            'data' => $this->buildData->execute($sections, $this->submittedValues($request)),
        ]);

        return redirect()
            ->route('asset-cards.show', $assetCard)
            ->with('success', 'Ficha de activo creada correctamente.');
    }

    public function show(AssetCard $assetCard): Response
    {
        $template = AssetCardTemplate::query()->findOrFail($assetCard->asset_card_template_id);
        $sections = $this->templateSections->forVersion($template, $assetCard->version);

        return Inertia::render('AssetCards/Show/index', [
            'assetCard' => [
                'id' => $assetCard->id,
                'code' => $assetCard->code,
            ],
            'template' => [
                'id' => $template->id,
                'name' => $template->name,
                'version' => $assetCard->version,
            ],
            'sections' => $sections,
            'values' => $this->valuesFromData($assetCard->data),
            'editUrl' => route('asset-cards.edit', $assetCard),
            'indexUrl' => route('asset-cards.index'),
        ]);
    }

    public function edit(AssetCard $assetCard): Response
    {
        $template = AssetCardTemplate::query()->findOrFail($assetCard->asset_card_template_id);
        $sections = $this->templateSections->forVersion($template, $assetCard->version);

        return Inertia::render('AssetCards/Edit/index', [
            'assetCard' => [
                'id' => $assetCard->id,
                'code' => $assetCard->code,
            ],
            'template' => [
                'id' => $template->id,
                'name' => $template->name,
                'version' => $assetCard->version,
            ],
            'sections' => $sections,
            'values' => $this->valuesFromData($assetCard->data),
            'action' => route('asset-cards.update', $assetCard),
            'cancelUrl' => route('asset-cards.show', $assetCard),
        ]);
    }

    public function update(Request $request, AssetCard $assetCard): RedirectResponse
    {
        $validated = $request->validate([
            'code' => [
                'required',
                'string',
                'max:255',
                Rule::unique('asset_cards', 'code')->ignore($assetCard->id),
            ],
            'values' => ['array'],
        ]);

        $template = AssetCardTemplate::query()->findOrFail($assetCard->asset_card_template_id);
        $sections = $this->templateSections->forVersion($template, $assetCard->version);
        $request->validate($this->rules->forSections($sections));

        $assetCard->update([
            'code' => (string) $validated['code'],
            'data' => $this->buildData->execute($sections, $this->submittedValues($request)),
        ]);

        return redirect()
            ->route('asset-cards.show', $assetCard)
            ->with('success', 'Ficha de activo actualizada correctamente.');
    }

    public function destroy(AssetCard $assetCard): RedirectResponse
    {
        $assetCard->delete();

        return redirect()
            ->route('asset-cards.index')
            ->with('success', 'Ficha de activo eliminada correctamente.');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function templatesWithVersions(): array
    {
        return AssetCardTemplate::query()
            ->orderBy('name')
            ->get()
            ->map(fn (AssetCardTemplate $template): array => [
                'id' => $template->id,
                'name' => $template->name,
                'version' => $template->version,
                'versions' => $template->versions()
                    ->orderByDesc('version')
                    ->pluck('version')
                    ->map(fn (mixed $value): int => (int) $value)
                    ->values()
                    ->all(),
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function valuesFromData(mixed $data): array
    {
        if (! is_array($data)) {
            return [];
        }

        $values = [];

        foreach ($data as $sectionId => $sectionFields) {
            if (! is_string($sectionId) || ! is_array($sectionFields)) {
                continue;
            }

            foreach ($sectionFields as $fieldName => $fieldData) {
                if (! is_array($fieldData)) {
                    continue;
                }

                $values[$sectionId][$fieldName] = $fieldData['value'] ?? null;
            }
        }

        return $values;
    }

    /**
     * @return array<string, mixed>
     */
    private function submittedValues(Request $request): array
    {
        $values = $request->input('values', []);

        return is_array($values) ? $values : [];
    }
}

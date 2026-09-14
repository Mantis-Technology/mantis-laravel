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
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
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
            'templates' => $this->templatesWithVersions(),
        ]);
    }

    public function create(Request $request): Response|RedirectResponse
    {
        $templateId = $request->integer('template_id');
        $version = $request->integer('version');

        if ($templateId <= 0 || $version <= 0) {
            return redirect()->route('asset-cards.index');
        }

        $template = AssetCardTemplate::query()->find($templateId);

        if (
            ! $template instanceof AssetCardTemplate ||
            $template->versions()->where('version', $version)->doesntExist()
        ) {
            return redirect()->route('asset-cards.index');
        }

        return Inertia::render('AssetCards/Create/index', [
            'template' => [
                'id' => $template->id,
                'name' => $template->name,
                'version' => $version,
            ],
            'sections' => $this->templateSections->forVersion($template, $version),
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

        $values = $this->resolveFileUploads(
            $request,
            $sections,
            $this->submittedValues($request),
            null
        );
        $this->ensureRequiredFiles($sections, $values);

        $assetCard = AssetCard::query()->create([
            'asset_card_template_id' => $template->id,
            'version' => $version,
            'code' => (string) $validated['code'],
            'data' => $this->buildData->execute($sections, $values),
        ]);

        return redirect()
            ->route('asset-cards.show', $assetCard)
            ->with('success', 'Ficha de activo creada correctamente.');
    }

    public function show(AssetCard $assetCard): Response
    {
        $template = AssetCardTemplate::query()->findOrFail($assetCard->asset_card_template_id);
        $sections = $this->templateSections->forVersion($template, $assetCard->version);
        $values = $this->valuesFromData($assetCard->data);

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
            'values' => $values,
            'fileUrls' => $this->fileUrls($assetCard, $sections, $values),
            'editUrl' => route('asset-cards.edit', $assetCard),
            'indexUrl' => route('asset-cards.index'),
        ]);
    }

    public function edit(AssetCard $assetCard): Response
    {
        $template = AssetCardTemplate::query()->findOrFail($assetCard->asset_card_template_id);
        $sections = $this->templateSections->forVersion($template, $assetCard->version);
        $values = $this->valuesFromData($assetCard->data);

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
            'values' => $values,
            'fileUrls' => $this->fileUrls($assetCard, $sections, $values),
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

        $values = $this->resolveFileUploads(
            $request,
            $sections,
            $this->submittedValues($request),
            $assetCard->data
        );
        $this->ensureRequiredFiles($sections, $values);

        $assetCard->update([
            'code' => (string) $validated['code'],
            'data' => $this->buildData->execute($sections, $values),
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
     * Resolves the file values for the given sections, storing new uploads on
     * the tenant disk and keeping the existing value when no new file or
     * removal flag is present.
     *
     * @param  array<int, array<string, mixed>>  $sections
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    private function resolveFileUploads(
        Request $request,
        array $sections,
        array $values,
        mixed $existingData
    ): array {
        $existing = is_array($existingData) ? $existingData : [];

        foreach ($sections as $section) {
            $sectionId = $section['id'] ?? null;
            $fields = $section['fields'] ?? [];

            if (! is_string($sectionId) || ! is_array($fields)) {
                continue;
            }

            foreach ($fields as $field) {
                if (! is_array($field) || ($field['type'] ?? null) !== 'file') {
                    continue;
                }

                $name = $field['name'] ?? null;

                if (! is_string($name)) {
                    continue;
                }

                $file = $request->file("files.{$sectionId}.{$name}");

                if ($file instanceof UploadedFile) {
                    $values[$sectionId][$name] = $this->storeFile($file, $sectionId, $name);

                    continue;
                }

                if ($request->boolean("remove_files.{$sectionId}.{$name}")) {
                    $values[$sectionId][$name] = null;

                    continue;
                }

                $existingValue = $existing[$sectionId][$name]['value'] ?? null;
                $values[$sectionId][$name] = is_string($existingValue) ? $existingValue : null;
            }
        }

        return $values;
    }

    /**
     * @param  array<int, array<string, mixed>>  $sections
     * @param  array<string, mixed>  $values
     *
     * @throws ValidationException
     */
    private function ensureRequiredFiles(array $sections, array $values): void
    {
        $errors = [];

        foreach ($sections as $section) {
            $sectionId = $section['id'] ?? null;
            $fields = $section['fields'] ?? [];

            if (! is_string($sectionId) || ! is_array($fields)) {
                continue;
            }

            foreach ($fields as $field) {
                if (
                    ! is_array($field) ||
                    ($field['type'] ?? null) !== 'file' ||
                    ! ($field['required'] ?? false)
                ) {
                    continue;
                }

                $name = $field['name'] ?? null;

                if (! is_string($name)) {
                    continue;
                }

                $value = $values[$sectionId][$name] ?? null;

                if (! is_string($value) || $value === '') {
                    $errors["values.{$sectionId}.{$name}"] = 'Este campo es obligatorio.';
                }
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    private function storeFile(UploadedFile $file, string $sectionId, string $fieldName): ?string
    {
        $extension = $file->getClientOriginalExtension();
        $baseName = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
        $baseName = $baseName !== '' ? $baseName : $fieldName;
        $suffix = $extension !== '' ? ".{$extension}" : '';

        $path = $file->storeAs(
            "asset-cards/{$sectionId}",
            "{$baseName}-".Str::random(8).$suffix,
            'local'
        );

        return is_string($path) ? $path : null;
    }

    /**
     * @param  array<int, array<string, mixed>>  $sections
     * @param  array<string, mixed>  $values
     * @return array<string, array<string, string>>
     */
    private function fileUrls(AssetCard $assetCard, array $sections, array $values): array
    {
        $urls = [];

        foreach ($sections as $section) {
            $sectionId = $section['id'] ?? null;
            $fields = $section['fields'] ?? [];

            if (! is_string($sectionId) || ! is_array($fields)) {
                continue;
            }

            foreach ($fields as $field) {
                if (! is_array($field) || ($field['type'] ?? null) !== 'file') {
                    continue;
                }

                $name = $field['name'] ?? null;

                if (! is_string($name)) {
                    continue;
                }

                $path = $values[$sectionId][$name] ?? null;

                if (! is_string($path) || $path === '') {
                    continue;
                }

                $urls[$sectionId][$name] = route('asset-cards.files.show', [
                    'assetCard' => $assetCard,
                    'section' => $sectionId,
                    'field' => $name,
                ]);
            }
        }

        return $urls;
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

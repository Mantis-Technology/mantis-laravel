<?php

declare(strict_types=1);

namespace App\Http\Controllers\AssetCardTemplates;

use App\Http\Controllers\Controller;
use App\Models\AssetCardTemplate;
use App\Models\AssetCardTemplateSection;
use App\Models\CardTemplateVersion;
use Inertia\Inertia;
use Inertia\Response;

class AssetCardTemplateBuilderController extends Controller
{
    public function __invoke(int $templateId, int $version): Response
    {
        $template = AssetCardTemplate::query()->findOrFail($templateId);

        if ($template->versions()->where('version', $version)->doesntExist()) {
            abort(404);
        }

        $sections = AssetCardTemplateSection::query()
            ->where('asset_card_template_id', $template->id)
            ->where('version', $version)
            ->orderBy('order')
            ->get()
            ->map(fn (AssetCardTemplateSection $section): array => [
                'id' => $section->getKey(),
                'name' => $section->name,
                'description' => $section->description,
                'order' => $section->order,
                'columns' => $section->columns,
                'fields' => $section->fields ?? [],
            ])
            ->values()
            ->all();

        $versions = CardTemplateVersion::query()
            ->where('asset_card_template_id', $template->id)
            ->orderByDesc('version')
            ->pluck('version')
            ->map(fn (mixed $versionNumber): array => [
                'version' => (int) $versionNumber,
                'url' => route('asset-card-templates.builder', [
                    'template' => $template->id,
                    'version' => (int) $versionNumber,
                ]),
                'is_current' => (int) $versionNumber === $version,
            ])
            ->values()
            ->all();

        return Inertia::render('AssetCardTemplates/Builder/index', [
            'template' => [
                'id' => $template->id,
                'name' => $template->name,
                'description' => $template->description,
                'version' => $version,
            ],
            'sections' => $sections,
            'versions' => $versions,
            'updateUrl' => route('asset-card-templates.builder.update', [
                'template' => $template->id,
                'version' => $version,
            ]),
            'previewUrl' => route('asset-card-templates.preview', [
                'template' => $template->id,
                'version' => $version,
            ]),
            'storeVersionUrl' => route('asset-card-templates.versions.store', [
                'template' => $template->id,
            ]),
        ]);
    }
}

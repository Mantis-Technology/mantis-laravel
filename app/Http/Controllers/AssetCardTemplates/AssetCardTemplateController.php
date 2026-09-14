<?php

declare(strict_types=1);

namespace App\Http\Controllers\AssetCardTemplates;

use App\Http\Controllers\Controller;
use App\Models\AssetCardTemplate;
use App\Services\CreateAssetCardTemplate;
use App\Services\CreateAssetCardTemplateVersion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AssetCardTemplateController extends Controller
{
    public function __construct(
        private CreateAssetCardTemplate $createTemplate,
        private CreateAssetCardTemplateVersion $createVersion
    ) {}

    public function index(): Response
    {
        $templates = AssetCardTemplate::query()
            ->orderBy('name')
            ->get()
            ->map(fn (AssetCardTemplate $template): array => [
                'id' => $template->id,
                'name' => $template->name,
                'description' => $template->description,
                'version' => $template->version,
                'sections_count' => $template->sections()
                    ->where('version', $template->version)
                    ->count(),
                'builder_url' => route('asset-card-templates.builder', [
                    'template' => $template->id,
                    'version' => $template->version,
                ]),
                'store_version_url' => route('asset-card-templates.versions.store', [
                    'template' => $template->id,
                ]),
            ])
            ->values()
            ->all();

        return Inertia::render('AssetCardTemplates/index', [
            'templates' => $templates,
            'createUrl' => route('asset-card-templates.create'),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('AssetCardTemplates/Create/index', [
            'action' => route('asset-card-templates.store'),
            'indexUrl' => route('asset-card-templates.index'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $template = $this->createTemplate->execute(
            $validated['name'],
            $validated['description'] ?? null
        );

        return redirect()
            ->route('asset-card-templates.builder', [
                'template' => $template->id,
                'version' => $template->version,
            ])
            ->with('success', 'Plantilla creada correctamente.');
    }

    public function storeVersion(AssetCardTemplate $template): RedirectResponse
    {
        $version = $this->createVersion->execute($template);

        return redirect()
            ->route('asset-card-templates.builder', [
                'template' => $template->id,
                'version' => $version,
            ])
            ->with('success', "Versión {$version} creada correctamente.");
    }
}

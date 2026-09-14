<?php

declare(strict_types=1);

namespace App\Http\Controllers\AssetCards;

use App\Http\Controllers\Controller;
use App\Models\AssetCard;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AssetCardFileController extends Controller
{
    public function __invoke(AssetCard $assetCard, string $section, string $field): StreamedResponse
    {
        $value = data_get($assetCard->data, [$section, $field, 'value']);
        $path = is_string($value) ? $value : null;

        abort_if($path === null || $path === '', 404);

        $disk = Storage::disk('local');

        abort_unless($disk->exists($path), 404);

        return $disk->response($path);
    }
}

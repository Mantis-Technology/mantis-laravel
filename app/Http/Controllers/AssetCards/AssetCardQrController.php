<?php

declare(strict_types=1);

namespace App\Http\Controllers\AssetCards;

use App\Http\Controllers\Controller;
use App\Models\AssetCard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AssetCardQrController extends Controller
{
    public function __invoke(Request $request, AssetCard $assetCard): StreamedResponse
    {
        $path = $assetCard->qr_path;

        abort_unless(is_string($path) && $path !== '', 404);

        $disk = Storage::disk('local');

        abort_unless($disk->exists($path), 404);

        $filename = "{$assetCard->code}-qr.svg";

        if ($request->boolean('download')) {
            return $disk->download($path, $filename, [
                'Content-Type' => 'image/svg+xml',
            ]);
        }

        return $disk->response($path, $filename, [
            'Content-Type' => 'image/svg+xml',
        ]);
    }
}

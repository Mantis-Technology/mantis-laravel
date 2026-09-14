<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AssetCard;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Facades\Storage;
use Throwable;

class GenerateAssetCardQrCode
{
    private const int SIZE = 320;

    private const int MARGIN = 2;

    /**
     * Generates a QR code pointing to the asset card and stores it on the
     * tenant disk, persisting its path on the model.
     */
    public function execute(AssetCard $assetCard): ?string
    {
        $svg = $this->render($assetCard->code);

        if ($svg === null) {
            return null;
        }

        $path = "asset-cards/{$assetCard->id}/qr.svg";

        Storage::disk('local')->put($path, $svg);

        $assetCard->update(['qr_path' => $path]);

        return $path;
    }

    private function render(string $content): ?string
    {
        try {
            $renderer = new ImageRenderer(
                new RendererStyle(self::SIZE, self::MARGIN),
                new SvgImageBackEnd
            );

            return (new Writer($renderer))->writeString($content);
        } catch (Throwable) {
            return null;
        }
    }
}

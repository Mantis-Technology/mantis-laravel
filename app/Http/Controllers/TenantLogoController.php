<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;

class TenantLogoController
{
    public function __invoke()
    {
        $path = tenant()?->meta['image'];

        abort_unless($path, 404);

        $disk = Storage::disk('tenant_logos');

        abort_unless($disk->exists($path), 404);

        return $disk->response($path);
    }
}
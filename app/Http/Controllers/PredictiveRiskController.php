<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\User;
use App\Services\Predictive\BuildFailureRiskPredictions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class PredictiveRiskController extends Controller
{
    public function __construct(
        private readonly BuildFailureRiskPredictions $predictions,
    ) {}

    public function show(Request $request): JsonResponse
    {
        $user = $request->user();

        abort_unless(
            $user instanceof User && $user->hasAnyRole([
                Role::TENANT_ADMINISTRATOR->value,
                Role::MAINTENANCE_CHIEF->value,
            ]),
            403,
        );

        return response()->json($this->predictions->forCurrentTenant());
    }
}

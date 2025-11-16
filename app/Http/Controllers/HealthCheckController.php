<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\BotHealthMonitor;
use Illuminate\Http\JsonResponse;

class HealthCheckController extends Controller
{
    public function __invoke(BotHealthMonitor $monitor): JsonResponse
    {
        $result = $monitor->check();

        $statusCode = match ($result['status']) {
            'healthy' => 200,
            'warning' => 200, // Still operational
            default => 503, // Service Unavailable
        };

        return response()->json($result, $statusCode);
    }
}

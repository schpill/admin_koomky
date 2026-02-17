<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Currency;
use App\Models\User;
use App\Services\ExchangeRates\ExchangeRateService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CurrencyController extends Controller
{
    use ApiResponse;

    public function index(): JsonResponse
    {
        $currencies = Currency::query()
            ->active()
            ->orderBy('code')
            ->get();

        return $this->success($currencies, 'Currencies retrieved successfully');
    }

    public function rates(Request $request, ExchangeRateService $service): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $baseCurrency = strtoupper((string) ($request->query('base') ?: ($user->base_currency ?? 'EUR')));

        return $this->success([
            'base_currency' => $baseCurrency,
            'rates' => $service->latestRates($baseCurrency),
        ], 'Currency rates retrieved successfully');
    }
}

<?php

namespace App\Http\Controllers\Api\V1\Accounting;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AccountingSettingsController extends Controller
{
    use ApiResponse;

    public function show(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return $this->success([
            'accounting_journal_sales' => $user->accounting_journal_sales,
            'accounting_journal_purchases' => $user->accounting_journal_purchases,
            'accounting_journal_bank' => $user->accounting_journal_bank,
            'accounting_auxiliary_prefix' => $user->accounting_auxiliary_prefix,
            'fiscal_year_start_month' => $user->fiscal_year_start_month,
        ], 'Accounting settings retrieved successfully');
    }

    public function update(Request $request): JsonResponse
    {
        $request->validate([
            'accounting_journal_sales' => 'sometimes|string|max:10',
            'accounting_journal_purchases' => 'sometimes|string|max:10',
            'accounting_journal_bank' => 'sometimes|string|max:10',
            'accounting_auxiliary_prefix' => 'nullable|string|max:10',
            'fiscal_year_start_month' => 'sometimes|integer|min:1|max:12',
        ]);

        /** @var User $user */
        $user = $request->user();
        $user->update($request->only([
            'accounting_journal_sales',
            'accounting_journal_purchases',
            'accounting_journal_bank',
            'accounting_auxiliary_prefix',
            'fiscal_year_start_month',
        ]));

        return $this->success([
            'accounting_journal_sales' => $user->accounting_journal_sales,
            'accounting_journal_purchases' => $user->accounting_journal_purchases,
            'accounting_journal_bank' => $user->accounting_journal_bank,
            'accounting_auxiliary_prefix' => $user->accounting_auxiliary_prefix,
            'fiscal_year_start_month' => $user->fiscal_year_start_month,
        ], 'Accounting settings updated successfully');
    }
}

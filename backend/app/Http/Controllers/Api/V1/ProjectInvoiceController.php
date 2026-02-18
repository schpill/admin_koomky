<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Invoices\InvoiceResource;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\TimeEntry;
use App\Models\User;
use App\Services\InvoiceCalculationService;
use App\Services\ReferenceGenerator;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class ProjectInvoiceController extends Controller
{
    use ApiResponse;

    public function generate(Request $request, Project $project, InvoiceCalculationService $calculationService): JsonResponse
    {
        Gate::authorize('update', $project);

        /** @var User $user */
        $user = $request->user();
        $includeBillableExpenses = $request->boolean('include_billable_expenses');

        $unbilledEntries = TimeEntry::query()
            ->whereHas('task', function ($query) use ($project): void {
                $query->where('project_id', $project->id);
            })
            ->where('is_billed', false)
            ->with('task')
            ->get();

        $billableExpenses = collect();
        if ($includeBillableExpenses) {
            $billableExpenses = Expense::query()
                ->where('user_id', $user->id)
                ->where('project_id', $project->id)
                ->where('is_billable', true)
                ->where('status', 'approved')
                ->orderBy('date')
                ->get();
        }

        if ($unbilledEntries->isEmpty() && $billableExpenses->isEmpty()) {
            return $this->error('No unbilled time entries or billable expenses found', 422);
        }

        $hourlyRate = (float) ($project->hourly_rate ?? 0);

        $timeLineItems = $unbilledEntries
            ->groupBy('task_id')
            ->map(function ($entries) use ($hourlyRate): array {
                $first = $entries->first();
                $taskTitle = $first?->task->title ?? 'Task';
                $minutes = (int) $entries->sum('duration_minutes');
                $hours = round($minutes / 60, 2);

                return [
                    'description' => 'Time entries - '.$taskTitle,
                    'quantity' => max(0.01, $hours),
                    'unit_price' => $hourlyRate,
                    'vat_rate' => 20,
                ];
            })
            ->values()
            ->all();

        $expenseLineItems = $billableExpenses
            ->values()
            ->map(function (Expense $expense): array {
                return [
                    'description' => 'Billable expense - '.$expense->description,
                    'quantity' => 1,
                    'unit_price' => (float) $expense->amount,
                    'vat_rate' => (float) ($expense->tax_rate ?? 0),
                ];
            })
            ->all();

        /** @var array<int, array<string, mixed>> $lineItems */
        $lineItems = [...$timeLineItems, ...$expenseLineItems];

        /** @var Invoice $invoice */
        $invoice = DB::transaction(function () use ($project, $user, $lineItems, $unbilledEntries, $calculationService): Invoice {
            $calculation = $calculationService->calculate($lineItems, null, null);

            $invoice = Invoice::query()->create([
                'user_id' => $project->user_id,
                'client_id' => $project->client_id,
                'project_id' => $project->id,
                'number' => ReferenceGenerator::generate('invoices', 'FAC'),
                'status' => 'draft',
                'issue_date' => now()->toDateString(),
                'due_date' => now()->addDays((int) ($user->payment_terms_days ?? 30))->toDateString(),
                'subtotal' => $calculation['subtotal'],
                'tax_amount' => $calculation['tax_amount'],
                'discount_type' => null,
                'discount_value' => null,
                'discount_amount' => 0,
                'total' => $calculation['total'],
                'currency' => 'EUR',
                'notes' => 'Generated from project '.$project->reference,
                'payment_terms' => ($user->payment_terms_days ?? 30).' days',
            ]);

            foreach ($lineItems as $index => $lineItem) {
                $invoice->lineItems()->create([
                    'description' => $lineItem['description'],
                    'quantity' => $lineItem['quantity'],
                    'unit_price' => $lineItem['unit_price'],
                    'vat_rate' => $lineItem['vat_rate'],
                    'sort_order' => $index,
                ]);
            }

            if ($unbilledEntries->isNotEmpty()) {
                TimeEntry::query()
                    ->whereIn('id', $unbilledEntries->pluck('id'))
                    ->update([
                        'is_billed' => true,
                        'billed_at' => now(),
                    ]);
            }

            return $invoice;
        });

        return $this->success(new InvoiceResource($invoice->load(['client', 'project', 'lineItems', 'payments'])), 'Invoice generated successfully', 201);
    }
}

<?php

namespace App\Observers;

use App\Models\Expense;
use App\Services\ActivityService;
use App\Services\WebhookDispatchService;

class ExpenseObserver
{
    public function created(Expense $expense): void
    {
        // Log activity if expense is linked to a client
        $client = $expense->client;
        if ($client) {
            ActivityService::log($client, "Expense created: {$expense->description}", [
                'expense_id' => $expense->id,
                'amount' => $expense->amount,
                'currency' => $expense->currency,
            ]);
        }

        // Dispatch webhook
        $this->dispatchWebhook($expense, 'expense.created');
    }

    public function updated(Expense $expense): void
    {
        // Log activity if expense is linked to a client
        $client = $expense->client;
        if ($client) {
            ActivityService::log($client, "Expense updated: {$expense->description}", [
                'expense_id' => $expense->id,
                'amount' => $expense->amount,
                'currency' => $expense->currency,
            ]);
        }

        // Dispatch webhook
        $this->dispatchWebhook($expense, 'expense.updated');
    }

    public function deleted(Expense $expense): void
    {
        // Log activity if expense is linked to a client
        $client = $expense->client;
        if ($client) {
            ActivityService::log($client, "Expense deleted: {$expense->description}", [
                'expense_id' => $expense->id,
            ]);
        }

        // Dispatch webhook
        $this->dispatchWebhook($expense, 'expense.deleted');
    }

    /**
     * Dispatch a webhook for the expense event.
     *
     * @param  array<string, mixed>  $extraData
     */
    private function dispatchWebhook(Expense $expense, string $event, array $extraData = []): void
    {
        $userId = $expense->user_id;

        $data = array_merge([
            'id' => $expense->id,
            'description' => $expense->description,
            'amount' => (float) $expense->amount,
            'currency' => $expense->currency,
            'date' => $expense->date->toDateString(),
            'category_id' => $expense->expense_category_id,
            'project_id' => $expense->project_id,
            'client_id' => $expense->client_id,
            'vendor' => $expense->vendor,
            'status' => $expense->status,
        ], $extraData);

        /** @var WebhookDispatchService $service */
        $service = app(WebhookDispatchService::class);
        $service->dispatch($event, $data, $userId);
    }
}

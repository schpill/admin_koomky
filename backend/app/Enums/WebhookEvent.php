<?php

namespace App\Enums;

/**
 * Enum defining all supported webhook events.
 *
 * These events can be subscribed to by webhook endpoints.
 */
enum WebhookEvent: string
{
    // Invoice events
    case INVOICE_CREATED = 'invoice.created';
    case INVOICE_SENT = 'invoice.sent';
    case INVOICE_PAID = 'invoice.paid';
    case INVOICE_OVERDUE = 'invoice.overdue';
    case INVOICE_CANCELLED = 'invoice.cancelled';

    // Quote events
    case QUOTE_CREATED = 'quote.created';
    case QUOTE_SENT = 'quote.sent';
    case QUOTE_ACCEPTED = 'quote.accepted';
    case QUOTE_REJECTED = 'quote.rejected';
    case QUOTE_EXPIRED = 'quote.expired';

    // Payment events
    case PAYMENT_RECEIVED = 'payment.received';

    // Client events
    case CLIENT_CREATED = 'client.created';
    case CLIENT_UPDATED = 'client.updated';
    case CLIENT_DELETED = 'client.deleted';

    // Expense events
    case EXPENSE_CREATED = 'expense.created';
    case EXPENSE_UPDATED = 'expense.updated';
    case EXPENSE_DELETED = 'expense.deleted';

    // Project events
    case PROJECT_COMPLETED = 'project.completed';
    case PROJECT_CANCELLED = 'project.cancelled';

    // Lead events
    case LEAD_CREATED = 'lead.created';
    case LEAD_STATUS_CHANGED = 'lead.status_changed';
    case LEAD_CONVERTED = 'lead.converted';

    // Test event
    case TEST_PING = 'test.ping';

    /**
     * Get all event values as an array.
     *
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(fn (self $event) => $event->value, self::cases());
    }

    /**
     * Get events grouped by category.
     *
     * @return array<string, array<int, string>>
     */
    public static function grouped(): array
    {
        return [
            'invoices' => [
                self::INVOICE_CREATED->value,
                self::INVOICE_SENT->value,
                self::INVOICE_PAID->value,
                self::INVOICE_OVERDUE->value,
                self::INVOICE_CANCELLED->value,
            ],
            'quotes' => [
                self::QUOTE_CREATED->value,
                self::QUOTE_SENT->value,
                self::QUOTE_ACCEPTED->value,
                self::QUOTE_REJECTED->value,
                self::QUOTE_EXPIRED->value,
            ],
            'payments' => [
                self::PAYMENT_RECEIVED->value,
            ],
            'clients' => [
                self::CLIENT_CREATED->value,
                self::CLIENT_UPDATED->value,
                self::CLIENT_DELETED->value,
            ],
            'expenses' => [
                self::EXPENSE_CREATED->value,
                self::EXPENSE_UPDATED->value,
                self::EXPENSE_DELETED->value,
            ],
            'projects' => [
                self::PROJECT_COMPLETED->value,
                self::PROJECT_CANCELLED->value,
            ],
            'leads' => [
                self::LEAD_CREATED->value,
                self::LEAD_STATUS_CHANGED->value,
                self::LEAD_CONVERTED->value,
            ],
        ];
    }

    /**
     * Get a human-readable description for the event.
     */
    public function description(): string
    {
        return match ($this) {
            self::INVOICE_CREATED => 'Invoice created',
            self::INVOICE_SENT => 'Invoice sent',
            self::INVOICE_PAID => 'Invoice paid',
            self::INVOICE_OVERDUE => 'Invoice overdue',
            self::INVOICE_CANCELLED => 'Invoice cancelled',
            self::QUOTE_CREATED => 'Quote created',
            self::QUOTE_SENT => 'Quote sent',
            self::QUOTE_ACCEPTED => 'Quote accepted',
            self::QUOTE_REJECTED => 'Quote rejected',
            self::QUOTE_EXPIRED => 'Quote expired',
            self::PAYMENT_RECEIVED => 'Payment received',
            self::CLIENT_CREATED => 'Client created',
            self::CLIENT_UPDATED => 'Client updated',
            self::CLIENT_DELETED => 'Client deleted',
            self::EXPENSE_CREATED => 'Expense created',
            self::EXPENSE_UPDATED => 'Expense updated',
            self::EXPENSE_DELETED => 'Expense deleted',
            self::PROJECT_COMPLETED => 'Project completed',
            self::PROJECT_CANCELLED => 'Project cancelled',
            self::LEAD_CREATED => 'Lead created',
            self::LEAD_STATUS_CHANGED => 'Lead status changed',
            self::LEAD_CONVERTED => 'Lead converted to client',
            self::TEST_PING => 'Test ping',
        };
    }
}

export const WEBHOOK_EVENTS = [
  "invoice.created",
  "invoice.sent",
  "invoice.paid",
  "invoice.overdue",
  "invoice.cancelled",
  "quote.sent",
  "quote.accepted",
  "quote.rejected",
  "quote.expired",
  "expense.created",
  "expense.updated",
  "expense.deleted",
  "project.completed",
  "project.cancelled",
  "payment.received",
  "lead.created",
  "lead.status_changed",
  "lead.converted",
] as const;

export type WebhookEvent = (typeof WEBHOOK_EVENTS)[number];

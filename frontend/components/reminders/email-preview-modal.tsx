"use client";

import { Dialog, DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import type { ReminderStepInput } from "@/lib/stores/reminders";

interface EmailPreviewModalProps {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  step: ReminderStepInput | null;
}

export function EmailPreviewModal({ open, onOpenChange, step }: EmailPreviewModalProps) {
  const template = step?.body || "";
  const html = template
    .replaceAll("{{client_name}}", "Jean Dupont")
    .replaceAll("{{invoice_number}}", "FAC-2026-0042")
    .replaceAll("{{invoice_amount}}", "1 200.00 EUR")
    .replaceAll("{{due_date}}", "2026-02-01")
    .replaceAll("{{days_overdue}}", "14")
    .replaceAll("{{pay_link}}", "https://example.com/pay")
    .replaceAll("\n", "<br/>");

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Aperçu email</DialogTitle>
        </DialogHeader>
        <div className="rounded border p-4 text-sm" dangerouslySetInnerHTML={{ __html: html }} />
      </DialogContent>
    </Dialog>
  );
}

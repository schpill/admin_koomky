"use client";

import { useMemo, useState } from "react";
import { Button } from "@/components/ui/button";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";

export interface RecordPaymentPayload {
  amount: number;
  payment_date: string;
  payment_method: string;
  reference: string;
  notes: string;
}

interface RecordPaymentModalProps {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  invoiceTotal: number;
  amountPaid: number;
  onSubmit: (payload: RecordPaymentPayload) => void | Promise<void>;
}

export function RecordPaymentModal({
  open,
  onOpenChange,
  invoiceTotal,
  amountPaid,
  onSubmit,
}: RecordPaymentModalProps) {
  const [amount, setAmount] = useState(0);
  const [paymentDate, setPaymentDate] = useState("");
  const [paymentMethod, setPaymentMethod] = useState("");
  const [reference, setReference] = useState("");
  const [notes, setNotes] = useState("");
  const [error, setError] = useState<string | null>(null);

  const remainingBalance = useMemo(
    () => Number(Math.max(0, invoiceTotal - amountPaid).toFixed(2)),
    [invoiceTotal, amountPaid]
  );

  const remainingAfterPayment = useMemo(
    () => Number(Math.max(0, remainingBalance - amount).toFixed(2)),
    [remainingBalance, amount]
  );

  const handleSubmit = async () => {
    if (amount <= 0) {
      setError("Amount must be greater than 0");
      return;
    }

    if (amount > remainingBalance) {
      setError(`Amount cannot exceed remaining balance (${remainingBalance.toFixed(2)})`);
      return;
    }

    if (!paymentDate) {
      setError("Payment date is required");
      return;
    }

    setError(null);

    await onSubmit({
      amount,
      payment_date: paymentDate,
      payment_method: paymentMethod,
      reference,
      notes,
    });

    onOpenChange(false);
  };

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Record payment</DialogTitle>
          <DialogDescription>
            Register a payment and update the invoice balance.
          </DialogDescription>
        </DialogHeader>

        <div className="space-y-3">
          <p className="text-sm text-muted-foreground">
            Remaining after payment: {remainingAfterPayment.toFixed(2)} EUR
          </p>

          <div className="space-y-2">
            <Label htmlFor="payment-amount">Amount</Label>
            <Input
              id="payment-amount"
              type="number"
              min="0"
              step="0.01"
              value={amount}
              onChange={(event) => setAmount(Number(event.target.value || 0))}
            />
          </div>

          <div className="space-y-2">
            <Label htmlFor="payment-date">Payment date</Label>
            <Input
              id="payment-date"
              type="date"
              value={paymentDate}
              onChange={(event) => setPaymentDate(event.target.value)}
            />
          </div>

          <div className="space-y-2">
            <Label htmlFor="payment-method">Payment method</Label>
            <Input
              id="payment-method"
              value={paymentMethod}
              onChange={(event) => setPaymentMethod(event.target.value)}
            />
          </div>

          <div className="space-y-2">
            <Label htmlFor="payment-reference">Reference</Label>
            <Input
              id="payment-reference"
              value={reference}
              onChange={(event) => setReference(event.target.value)}
            />
          </div>

          <div className="space-y-2">
            <Label htmlFor="payment-notes">Notes</Label>
            <Textarea
              id="payment-notes"
              rows={3}
              value={notes}
              onChange={(event) => setNotes(event.target.value)}
            />
          </div>

          {error ? <p className="text-sm text-destructive">{error}</p> : null}

          <div className="flex justify-end">
            <Button type="button" onClick={handleSubmit}>
              Record payment
            </Button>
          </div>
        </div>
      </DialogContent>
    </Dialog>
  );
}

"use client";

import { useEffect, useState } from "react";
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

interface SendInvoiceModalProps {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  invoiceNumber: string;
  clientEmail?: string | null;
  onSubmit: (payload: {
    subject: string;
    body: string;
  }) => void | Promise<void>;
}

export function SendInvoiceModal({
  open,
  onOpenChange,
  invoiceNumber,
  clientEmail,
  onSubmit,
}: SendInvoiceModalProps) {
  const [subject, setSubject] = useState("");
  const [body, setBody] = useState("");

  useEffect(() => {
    setSubject(`Invoice ${invoiceNumber}`);
    setBody(
      `Hello,\n\nPlease find attached invoice ${invoiceNumber}.\n\nThank you.`
    );
  }, [invoiceNumber]);

  const handleSubmit = async () => {
    await onSubmit({ subject, body });
    onOpenChange(false);
  };

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Send invoice</DialogTitle>
          <DialogDescription>
            Review the email content before sending the invoice.
          </DialogDescription>
        </DialogHeader>

        <div className="space-y-3">
          <p className="text-sm text-muted-foreground">
            Recipient: {clientEmail || "No email available"}
          </p>

          <div className="space-y-2">
            <Label htmlFor="invoice-send-subject">Subject</Label>
            <Input
              id="invoice-send-subject"
              value={subject}
              onChange={(event) => setSubject(event.target.value)}
            />
          </div>

          <div className="space-y-2">
            <Label htmlFor="invoice-send-body">Message</Label>
            <Textarea
              id="invoice-send-body"
              rows={6}
              value={body}
              onChange={(event) => setBody(event.target.value)}
            />
          </div>

          <div className="flex justify-end">
            <Button type="button" onClick={handleSubmit}>
              Confirm send
            </Button>
          </div>
        </div>
      </DialogContent>
    </Dialog>
  );
}

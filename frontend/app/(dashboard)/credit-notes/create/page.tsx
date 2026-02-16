"use client";

import Link from "next/link";
import { useEffect, useMemo, useState } from "react";
import { useRouter } from "next/navigation";
import { toast } from "sonner";
import { ChevronLeft } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Label } from "@/components/ui/label";
import { Input } from "@/components/ui/input";
import { Textarea } from "@/components/ui/textarea";
import {
  LineItemsEditor,
  type InvoiceLineItemInput,
} from "@/components/invoices/line-items-editor";
import { useInvoiceStore } from "@/lib/stores/invoices";
import { useCreditNoteStore } from "@/lib/stores/creditNotes";

const today = new Date().toISOString().slice(0, 10);

export default function CreateCreditNotePage() {
  const router = useRouter();
  const { invoices, currentInvoice, fetchInvoices, fetchInvoice } =
    useInvoiceStore();
  const { createCreditNote, isLoading } = useCreditNoteStore();

  const [invoiceId, setInvoiceId] = useState("");
  const [issueDate, setIssueDate] = useState(today);
  const [reason, setReason] = useState("");
  const [lineItems, setLineItems] = useState<InvoiceLineItemInput[]>([]);

  useEffect(() => {
    fetchInvoices({ per_page: 100, sort_by: "issue_date", sort_order: "desc" });
  }, [fetchInvoices]);

  useEffect(() => {
    if (!invoiceId) {
      setLineItems([]);
      return;
    }

    fetchInvoice(invoiceId)
      .then((invoice) => {
        const prefilled = (invoice?.line_items || []).map((line) => ({
          description: line.description,
          quantity: Number(line.quantity),
          unit_price: Number(line.unit_price),
          vat_rate: Number(line.vat_rate),
        }));

        setLineItems(prefilled.length > 0 ? prefilled : []);
      })
      .catch((error) => {
        toast.error(
          (error as Error).message || "Unable to load invoice details"
        );
      });
  }, [invoiceId, fetchInvoice]);

  const selectedInvoice = useMemo(() => {
    return currentInvoice?.id === invoiceId
      ? currentInvoice
      : invoices.find((invoice) => invoice.id === invoiceId) || null;
  }, [currentInvoice, invoiceId, invoices]);

  const handleSubmit = async () => {
    if (!invoiceId) {
      toast.error("Please select an invoice");
      return;
    }

    const sanitizedItems = lineItems
      .map((line) => ({
        ...line,
        description: line.description.trim(),
      }))
      .filter((line) => line.description.length > 0);

    if (sanitizedItems.length === 0) {
      toast.error("At least one line item is required");
      return;
    }

    try {
      const created = await createCreditNote({
        invoice_id: invoiceId,
        issue_date: issueDate,
        reason,
        line_items: sanitizedItems,
      });

      toast.success("Credit note created");

      if (created?.id) {
        router.push(`/credit-notes/${created.id}`);
      } else {
        router.push("/credit-notes");
      }
    } catch (error) {
      toast.error((error as Error).message || "Unable to create credit note");
    }
  };

  return (
    <div className="space-y-6">
      <div className="space-y-2">
        <Button variant="ghost" asChild className="-ml-2">
          <Link href="/credit-notes">
            <ChevronLeft className="mr-2 h-4 w-4" />
            Back to credit notes
          </Link>
        </Button>
        <h1 className="text-3xl font-bold">Create credit note</h1>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Credit note information</CardTitle>
        </CardHeader>
        <CardContent className="space-y-4">
          <div className="grid gap-3 md:grid-cols-2">
            <div className="space-y-2">
              <Label htmlFor="credit-note-invoice">Invoice</Label>
              <select
                id="credit-note-invoice"
                className="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                value={invoiceId}
                onChange={(event) => setInvoiceId(event.target.value)}
              >
                <option value="">Select invoice</option>
                {invoices.map((invoice) => (
                  <option key={invoice.id} value={invoice.id}>
                    {invoice.number} -{" "}
                    {Number(invoice.balance_due || 0).toFixed(2)} EUR due
                  </option>
                ))}
              </select>
            </div>

            <div className="space-y-2">
              <Label htmlFor="credit-note-issue-date">Issue date</Label>
              <Input
                id="credit-note-issue-date"
                type="date"
                value={issueDate}
                onChange={(event) => setIssueDate(event.target.value)}
              />
            </div>
          </div>

          {selectedInvoice && (
            <div className="rounded-md border bg-muted/30 p-3 text-sm">
              <p className="font-medium">
                Selected invoice: {selectedInvoice.number}
              </p>
              <p className="text-muted-foreground">
                Remaining balance:{" "}
                {Number(selectedInvoice.balance_due || 0).toFixed(2)} EUR
              </p>
            </div>
          )}

          <LineItemsEditor
            items={lineItems}
            discountType={null}
            discountValue={0}
            onItemsChange={setLineItems}
            onDiscountTypeChange={() => undefined}
            onDiscountValueChange={() => undefined}
          />

          <div className="space-y-2">
            <Label htmlFor="credit-note-reason">Reason</Label>
            <Textarea
              id="credit-note-reason"
              rows={4}
              value={reason}
              onChange={(event) => setReason(event.target.value)}
            />
          </div>

          <div className="flex justify-end">
            <Button type="button" onClick={handleSubmit} disabled={isLoading}>
              {isLoading ? "Saving..." : "Save draft"}
            </Button>
          </div>
        </CardContent>
      </Card>
    </div>
  );
}

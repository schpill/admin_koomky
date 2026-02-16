"use client";

import Link from "next/link";
import { useEffect, useState } from "react";
import { useRouter } from "next/navigation";
import { toast } from "sonner";
import { ChevronLeft } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import {
  LineItemsEditor,
  type InvoiceLineItemInput,
} from "@/components/invoices/line-items-editor";
import { useClientStore } from "@/lib/stores/clients";
import { useQuoteStore } from "@/lib/stores/quotes";

const today = new Date().toISOString().slice(0, 10);
const inThirtyDays = new Date(Date.now() + 30 * 24 * 60 * 60 * 1000)
  .toISOString()
  .slice(0, 10);

export default function CreateQuotePage() {
  const router = useRouter();
  const { clients, fetchClients } = useClientStore();
  const { createQuote, isLoading } = useQuoteStore();

  const [clientId, setClientId] = useState("");
  const [issueDate, setIssueDate] = useState(today);
  const [validUntil, setValidUntil] = useState(inThirtyDays);
  const [notes, setNotes] = useState("");
  const [discountType, setDiscountType] = useState<
    "percentage" | "fixed" | null
  >(null);
  const [discountValue, setDiscountValue] = useState(0);
  const [lineItems, setLineItems] = useState<InvoiceLineItemInput[]>([
    {
      description: "",
      quantity: 1,
      unit_price: 0,
      vat_rate: 20,
    },
  ]);

  useEffect(() => {
    fetchClients();
  }, [fetchClients]);

  const handleSubmit = async () => {
    if (!clientId) {
      toast.error("Please select a client");
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
      const created = await createQuote({
        client_id: clientId,
        issue_date: issueDate,
        valid_until: validUntil,
        notes,
        discount_type: discountType,
        discount_value: discountType ? discountValue : null,
        line_items: sanitizedItems,
      });

      toast.success("Quote created");

      if (created?.id) {
        router.push(`/quotes/${created.id}`);
      } else {
        router.push("/quotes");
      }
    } catch (error) {
      toast.error((error as Error).message || "Unable to create quote");
    }
  };

  return (
    <div className="space-y-6">
      <div className="space-y-2">
        <Button variant="ghost" asChild className="-ml-2">
          <Link href="/quotes">
            <ChevronLeft className="mr-2 h-4 w-4" />
            Back to quotes
          </Link>
        </Button>
        <h1 className="text-3xl font-bold">Create quote</h1>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Quote information</CardTitle>
        </CardHeader>
        <CardContent className="space-y-4">
          <div className="grid gap-3 md:grid-cols-3">
            <div className="space-y-2">
              <Label htmlFor="quote-client">Client</Label>
              <select
                id="quote-client"
                className="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                value={clientId}
                onChange={(event) => setClientId(event.target.value)}
              >
                <option value="">Select client</option>
                {clients.map((client) => (
                  <option key={client.id} value={client.id}>
                    {client.name}
                  </option>
                ))}
              </select>
            </div>
            <div className="space-y-2">
              <Label htmlFor="quote-issue-date">Issue date</Label>
              <Input
                id="quote-issue-date"
                type="date"
                value={issueDate}
                onChange={(event) => setIssueDate(event.target.value)}
              />
            </div>
            <div className="space-y-2">
              <Label htmlFor="quote-valid-until">Valid until</Label>
              <Input
                id="quote-valid-until"
                type="date"
                value={validUntil}
                onChange={(event) => setValidUntil(event.target.value)}
              />
            </div>
          </div>

          <LineItemsEditor
            items={lineItems}
            discountType={discountType}
            discountValue={discountValue}
            onItemsChange={setLineItems}
            onDiscountTypeChange={setDiscountType}
            onDiscountValueChange={setDiscountValue}
          />

          <div className="space-y-2">
            <Label htmlFor="quote-notes">Notes</Label>
            <Textarea
              id="quote-notes"
              rows={4}
              value={notes}
              onChange={(event) => setNotes(event.target.value)}
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

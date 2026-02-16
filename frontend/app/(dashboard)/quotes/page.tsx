"use client";

import Link from "next/link";
import { useEffect, useMemo, useState } from "react";
import { Plus } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { EmptyState } from "@/components/ui/empty-state";
import { Skeleton } from "@/components/ui/skeleton";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { useQuoteStore, type Quote } from "@/lib/stores/quotes";
import { useClientStore } from "@/lib/stores/clients";
import { QuoteStatusBadge } from "@/components/quotes/quote-status-badge";
import { QuotePdfPreview } from "@/components/quotes/quote-pdf-preview";

function buildPreviewHtml(quote?: Quote | null): string {
  if (!quote) {
    return "";
  }

  const lineRows = (quote.line_items || [])
    .map(
      (line) => `
      <tr>
        <td>${line.description}</td>
        <td>${Number(line.quantity).toFixed(2)}</td>
        <td>${Number(line.unit_price).toFixed(2)}</td>
        <td>${Number(line.total || line.quantity * line.unit_price).toFixed(2)}</td>
      </tr>
    `
    )
    .join("");

  return `
    <html>
      <body style="font-family: Arial, sans-serif; padding: 16px;">
        <h2>${quote.number}</h2>
        <p>Client: ${quote.client?.name || quote.client_id}</p>
        <p>Issue date: ${quote.issue_date}</p>
        <p>Valid until: ${quote.valid_until}</p>
        <table style="width: 100%; border-collapse: collapse;" border="1" cellpadding="6">
          <thead>
            <tr><th>Description</th><th>Qty</th><th>Unit</th><th>Total</th></tr>
          </thead>
          <tbody>${lineRows}</tbody>
        </table>
        <p style="margin-top: 12px;"><strong>Total:</strong> ${Number(quote.total).toFixed(2)} EUR</p>
      </body>
    </html>
  `;
}

export default function QuotesPage() {
  const { quotes, isLoading, pagination, fetchQuotes } = useQuoteStore();
  const { clients, fetchClients } = useClientStore();
  const [selectedQuoteId, setSelectedQuoteId] = useState<string | null>(null);

  const [status, setStatus] = useState("");
  const [clientId, setClientId] = useState("");
  const [dateFrom, setDateFrom] = useState("");
  const [dateTo, setDateTo] = useState("");

  useEffect(() => {
    fetchClients();
  }, [fetchClients]);

  useEffect(() => {
    fetchQuotes({
      status,
      client_id: clientId,
      date_from: dateFrom,
      date_to: dateTo,
      sort_by: "issue_date",
      sort_order: "desc",
    });
  }, [fetchQuotes, status, clientId, dateFrom, dateTo]);

  const selectedQuote = useMemo(() => {
    return (
      quotes.find((quote) => quote.id === selectedQuoteId) || quotes[0] || null
    );
  }, [quotes, selectedQuoteId]);

  return (
    <div className="space-y-6">
      <div className="flex flex-wrap items-center justify-between gap-3">
        <div>
          <h1 className="text-3xl font-bold">Quotes</h1>
          <p className="text-sm text-muted-foreground">
            {pagination ? `${pagination.total} quotes` : ""}
          </p>
        </div>
        <Button asChild>
          <Link href="/quotes/create">
            <Plus className="mr-2 h-4 w-4" />
            New quote
          </Link>
        </Button>
      </div>

      <div className="rounded-lg border p-4">
        <div className="grid gap-3 md:grid-cols-4">
          <div className="space-y-2">
            <Label htmlFor="quote-filter-status">Status</Label>
            <select
              id="quote-filter-status"
              className="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
              value={status}
              onChange={(event) => setStatus(event.target.value)}
            >
              <option value="">All statuses</option>
              <option value="draft">Draft</option>
              <option value="sent">Sent</option>
              <option value="accepted">Accepted</option>
              <option value="rejected">Rejected</option>
              <option value="expired">Expired</option>
            </select>
          </div>

          <div className="space-y-2">
            <Label htmlFor="quote-filter-client">Client</Label>
            <select
              id="quote-filter-client"
              className="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
              value={clientId}
              onChange={(event) => setClientId(event.target.value)}
            >
              <option value="">All clients</option>
              {clients.map((client) => (
                <option key={client.id} value={client.id}>
                  {client.name}
                </option>
              ))}
            </select>
          </div>

          <div className="space-y-2">
            <Label htmlFor="quote-filter-from">From</Label>
            <Input
              id="quote-filter-from"
              type="date"
              value={dateFrom}
              onChange={(event) => setDateFrom(event.target.value)}
            />
          </div>

          <div className="space-y-2">
            <Label htmlFor="quote-filter-to">To</Label>
            <Input
              id="quote-filter-to"
              type="date"
              value={dateTo}
              onChange={(event) => setDateTo(event.target.value)}
            />
          </div>
        </div>
      </div>

      <div className="grid gap-6 lg:grid-cols-[2fr_1fr]">
        <Card>
          <CardHeader>
            <CardTitle>Quote list</CardTitle>
          </CardHeader>
          <CardContent>
            {isLoading && quotes.length === 0 ? (
              <div className="space-y-3">
                <Skeleton className="h-12 w-full" />
                <Skeleton className="h-12 w-full" />
              </div>
            ) : quotes.length === 0 ? (
              <EmptyState
                title="No quotes"
                description="Create your first quote to start your sales pipeline."
              />
            ) : (
              <div className="overflow-x-auto">
                <table className="w-full text-sm">
                  <thead>
                    <tr className="border-b text-left">
                      <th className="pb-3 font-medium text-muted-foreground">
                        Number
                      </th>
                      <th className="pb-3 font-medium text-muted-foreground">
                        Client
                      </th>
                      <th className="pb-3 font-medium text-muted-foreground">
                        Issue date
                      </th>
                      <th className="pb-3 font-medium text-muted-foreground">
                        Valid until
                      </th>
                      <th className="pb-3 font-medium text-muted-foreground">
                        Total
                      </th>
                      <th className="pb-3 font-medium text-muted-foreground">
                        Status
                      </th>
                    </tr>
                  </thead>
                  <tbody>
                    {quotes.map((quote) => (
                      <tr
                        key={quote.id}
                        className="cursor-pointer border-b last:border-0 hover:bg-muted/30"
                        onClick={() => setSelectedQuoteId(quote.id)}
                      >
                        <td className="py-4">
                          <Link
                            href={`/quotes/${quote.id}`}
                            className="font-medium text-primary hover:underline"
                          >
                            {quote.number}
                          </Link>
                        </td>
                        <td className="py-4 text-muted-foreground">
                          {quote.client?.name || quote.client_id}
                        </td>
                        <td className="py-4 text-muted-foreground">
                          {quote.issue_date}
                        </td>
                        <td className="py-4 text-muted-foreground">
                          {quote.valid_until}
                        </td>
                        <td className="py-4">
                          {Number(quote.total || 0).toFixed(2)} EUR
                        </td>
                        <td className="py-4">
                          <QuoteStatusBadge status={quote.status} />
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            )}
          </CardContent>
        </Card>

        <QuotePdfPreview html={buildPreviewHtml(selectedQuote)} />
      </div>
    </div>
  );
}

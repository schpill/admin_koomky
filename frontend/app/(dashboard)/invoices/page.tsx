"use client";

import Link from "next/link";
import { useEffect, useMemo, useState } from "react";
import { Plus } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { EmptyState } from "@/components/ui/empty-state";
import { Skeleton } from "@/components/ui/skeleton";
import { useInvoiceStore, type Invoice } from "@/lib/stores/invoices";
import { useClientStore } from "@/lib/stores/clients";
import { InvoiceFilterBar } from "@/components/invoices/invoice-filter-bar";
import { InvoiceStatusBadge } from "@/components/invoices/invoice-status-badge";
import { InvoicePdfPreview } from "@/components/invoices/invoice-pdf-preview";
import { CurrencyAmount } from "@/components/shared/currency-amount";

function buildPreviewHtml(invoice?: Invoice | null): string {
  if (!invoice) {
    return "";
  }

  const lineRows = (invoice.line_items || [])
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
        <h2>${invoice.number}</h2>
        <p>Client: ${invoice.client?.name || invoice.client_id}</p>
        <p>Issue date: ${invoice.issue_date}</p>
        <p>Due date: ${invoice.due_date}</p>
        <table style="width: 100%; border-collapse: collapse;" border="1" cellpadding="6">
          <thead>
            <tr><th>Description</th><th>Qty</th><th>Unit</th><th>Total</th></tr>
          </thead>
          <tbody>${lineRows}</tbody>
        </table>
        <p style="margin-top: 12px;"><strong>Total:</strong> ${Number(invoice.total).toFixed(2)} ${invoice.currency || "EUR"}</p>
      </body>
    </html>
  `;
}

export default function InvoicesPage() {
  const { invoices, isLoading, pagination, fetchInvoices } = useInvoiceStore();
  const { clients, fetchClients } = useClientStore();
  const [filters, setFilters] = useState<Record<string, string>>({
    sort_by: "issue_date",
    sort_order: "desc",
  });
  const [selectedInvoiceId, setSelectedInvoiceId] = useState<string | null>(
    null
  );

  useEffect(() => {
    fetchClients();
  }, [fetchClients]);

  useEffect(() => {
    fetchInvoices(filters);
  }, [fetchInvoices, filters]);

  const selectedInvoice = useMemo(() => {
    return (
      invoices.find((invoice) => invoice.id === selectedInvoiceId) ||
      invoices[0] ||
      null
    );
  }, [invoices, selectedInvoiceId]);

  return (
    <div className="space-y-6">
      <div className="flex flex-wrap items-center justify-between gap-3">
        <div>
          <h1 className="text-3xl font-bold">Invoices</h1>
          <p className="text-sm text-muted-foreground">
            {pagination ? `${pagination.total} invoices` : ""}
          </p>
        </div>
        <Button asChild>
          <Link href="/invoices/create">
            <Plus className="mr-2 h-4 w-4" />
            New invoice
          </Link>
        </Button>
      </div>

      <InvoiceFilterBar
        clients={clients.map((client) => ({
          id: client.id,
          name: client.name,
        }))}
        onApply={(nextFilters) => setFilters({ ...filters, ...nextFilters })}
      />

      <div className="grid gap-6 lg:grid-cols-[2fr_1fr]">
        <Card>
          <CardHeader>
            <CardTitle>Invoice list</CardTitle>
          </CardHeader>
          <CardContent>
            {isLoading && invoices.length === 0 ? (
              <div className="space-y-3">
                <Skeleton className="h-12 w-full" />
                <Skeleton className="h-12 w-full" />
              </div>
            ) : invoices.length === 0 ? (
              <EmptyState
                title="No invoices"
                description="Create your first invoice to start tracking payments."
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
                        Due date
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
                    {invoices.map((invoice) => (
                      <tr
                        key={invoice.id}
                        className="cursor-pointer border-b last:border-0 hover:bg-muted/30"
                        onClick={() => setSelectedInvoiceId(invoice.id)}
                      >
                        <td className="py-4">
                          <Link
                            href={`/invoices/${invoice.id}`}
                            className="font-medium text-primary hover:underline"
                          >
                            {invoice.number}
                          </Link>
                          {invoice.recurring_invoice_profile_id && (
                            <Badge
                              variant="outline"
                              className="ml-2 align-middle text-[10px] uppercase"
                            >
                              Recurring
                            </Badge>
                          )}
                        </td>
                        <td className="py-4 text-muted-foreground">
                          {invoice.client?.name || invoice.client_id}
                        </td>
                        <td className="py-4 text-muted-foreground">
                          {invoice.issue_date}
                        </td>
                        <td className="py-4 text-muted-foreground">
                          {invoice.due_date}
                        </td>
                        <td className="py-4">
                          <CurrencyAmount
                            amount={Number(invoice.total || 0)}
                            currency={invoice.currency || "EUR"}
                          />
                        </td>
                        <td className="py-4">
                          <InvoiceStatusBadge status={invoice.status} />
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            )}
          </CardContent>
        </Card>

        <InvoicePdfPreview html={buildPreviewHtml(selectedInvoice)} />
      </div>
    </div>
  );
}

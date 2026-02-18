"use client";

import { useEffect, useState } from "react";
import Link from "next/link";
import { AlertCircle } from "lucide-react";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { CurrencyAmount } from "@/components/shared/currency-amount";
import { portalApiClient } from "@/lib/portal";

interface PortalInvoice {
  id: string;
  number: string;
  issue_date: string;
  due_date: string;
  status: string;
  total: number;
  currency: string;
}

interface PortalInvoiceListResponse {
  data: PortalInvoice[];
  total: number;
}

export default function PortalInvoicesPage() {
  const [invoices, setInvoices] = useState<PortalInvoice[]>([]);
  const [isLoading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    portalApiClient
      .get<PortalInvoiceListResponse>("/portal/invoices")
      .then((response) => {
        setInvoices(response.data?.data || []);
        setError(null);
      })
      .catch((err) => setError((err as Error).message))
      .finally(() => setLoading(false));
  }, []);

  return (
    <Card>
      <CardHeader>
        <CardTitle>Invoices</CardTitle>
      </CardHeader>
      <CardContent>
        {isLoading ? (
          <p className="text-sm text-muted-foreground">Loading invoices...</p>
        ) : error ? (
          <p className="inline-flex items-center gap-2 text-sm text-destructive">
            <AlertCircle className="h-4 w-4" />
            {error}
          </p>
        ) : invoices.length === 0 ? (
          <p className="text-sm text-muted-foreground">
            No invoices available in your portal.
          </p>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-sm">
              <thead>
                <tr className="border-b text-left">
                  <th className="pb-3">Number</th>
                  <th className="pb-3">Issue date</th>
                  <th className="pb-3">Due date</th>
                  <th className="pb-3">Status</th>
                  <th className="pb-3">Total</th>
                </tr>
              </thead>
              <tbody>
                {invoices.map((invoice) => (
                  <tr key={invoice.id} className="border-b last:border-0">
                    <td className="py-3">
                      <Link
                        href={`/portal/invoices/${invoice.id}`}
                        className="font-medium text-primary hover:underline"
                      >
                        {invoice.number}
                      </Link>
                    </td>
                    <td className="py-3 text-muted-foreground">
                      {invoice.issue_date}
                    </td>
                    <td className="py-3 text-muted-foreground">
                      {invoice.due_date}
                    </td>
                    <td className="py-3">
                      <Badge variant="outline">{invoice.status}</Badge>
                    </td>
                    <td className="py-3">
                      <CurrencyAmount
                        amount={Number(invoice.total || 0)}
                        currency={invoice.currency || "EUR"}
                      />
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </CardContent>
    </Card>
  );
}

"use client";

import { useEffect, useState } from "react";
import Link from "next/link";
import { AlertCircle, FileText, ReceiptText } from "lucide-react";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
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

interface PortalQuote {
  id: string;
  number: string;
  issue_date: string;
  valid_until: string;
  status: string;
  total: number;
  currency: string;
}

interface PortalPaymentHistory {
  id: string;
  invoice_id: string;
  amount: number;
  currency: string;
  status: string;
  paid_at?: string | null;
  created_at?: string | null;
}

interface PortalDashboardResponse {
  welcome_message?: string;
  outstanding_invoices: {
    count: number;
    total: number;
    currency: string;
  };
  recent_invoices: PortalInvoice[];
  recent_quotes: PortalQuote[];
  recent_payments?: PortalPaymentHistory[];
  freelancer?: {
    name?: string | null;
    business_name?: string | null;
  };
}

export default function PortalDashboardPage() {
  const [data, setData] = useState<PortalDashboardResponse | null>(null);
  const [isLoading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    portalApiClient
      .get<PortalDashboardResponse>("/portal/dashboard")
      .then((response) => {
        setData(response.data);
        setError(null);
      })
      .catch((err) => {
        setError((err as Error).message);
      })
      .finally(() => setLoading(false));
  }, []);

  if (isLoading) {
    return (
      <div className="rounded-xl border bg-card p-6 text-sm text-muted-foreground">
        Loading your portal dashboard...
      </div>
    );
  }

  if (error || !data) {
    return (
      <div className="rounded-xl border border-destructive/30 bg-destructive/5 p-6 text-sm text-destructive">
        <p className="inline-flex items-center gap-2">
          <AlertCircle className="h-4 w-4" />
          {error || "Unable to load portal dashboard."}
        </p>
      </div>
    );
  }

  const currency = data.outstanding_invoices?.currency || "EUR";

  return (
    <div className="space-y-6">
      <div className="space-y-1">
        <h1 className="text-2xl font-bold">Portal Dashboard</h1>
        <p className="text-sm text-muted-foreground">
          {data.welcome_message ||
            `Welcome to ${
              data.freelancer?.business_name || data.freelancer?.name || "your"
            } workspace.`}
        </p>
      </div>

      <div className="grid gap-4 md:grid-cols-3">
        <Card>
          <CardHeader>
            <CardTitle className="text-sm">Outstanding invoices</CardTitle>
          </CardHeader>
          <CardContent>
            <p className="text-2xl font-semibold">
              {data.outstanding_invoices?.count || 0}
            </p>
          </CardContent>
        </Card>
        <Card>
          <CardHeader>
            <CardTitle className="text-sm">Outstanding amount</CardTitle>
          </CardHeader>
          <CardContent>
            <p className="text-2xl font-semibold">
              <CurrencyAmount
                amount={Number(data.outstanding_invoices?.total || 0)}
                currency={currency}
              />
            </p>
          </CardContent>
        </Card>
        <Card>
          <CardHeader>
            <CardTitle className="text-sm">Quick actions</CardTitle>
          </CardHeader>
          <CardContent className="flex gap-2">
            <Button asChild variant="outline" size="sm">
              <Link href="/portal/invoices">View invoices</Link>
            </Button>
            <Button asChild variant="outline" size="sm">
              <Link href="/portal/quotes">View quotes</Link>
            </Button>
          </CardContent>
        </Card>
      </div>

      <div className="grid gap-6 lg:grid-cols-2">
        <Card>
          <CardHeader className="flex flex-row items-center justify-between">
            <CardTitle className="text-base">Recent invoices</CardTitle>
            <FileText className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent className="space-y-3">
            {(data.recent_invoices || []).length === 0 ? (
              <p className="text-sm text-muted-foreground">
                No invoices available yet.
              </p>
            ) : (
              data.recent_invoices.map((invoice) => (
                <Link
                  key={invoice.id}
                  href={`/portal/invoices/${invoice.id}`}
                  className="flex items-center justify-between rounded-md border p-3 hover:bg-muted/40"
                >
                  <div>
                    <p className="font-medium">{invoice.number}</p>
                    <p className="text-xs text-muted-foreground">
                      Due {invoice.due_date}
                    </p>
                  </div>
                  <div className="text-right">
                    <Badge variant="outline" className="mb-1">
                      {invoice.status}
                    </Badge>
                    <p className="text-sm">
                      <CurrencyAmount
                        amount={Number(invoice.total || 0)}
                        currency={invoice.currency || currency}
                      />
                    </p>
                  </div>
                </Link>
              ))
            )}
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-row items-center justify-between">
            <CardTitle className="text-base">Recent quotes</CardTitle>
            <ReceiptText className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent className="space-y-3">
            {(data.recent_quotes || []).length === 0 ? (
              <p className="text-sm text-muted-foreground">
                No quotes available yet.
              </p>
            ) : (
              data.recent_quotes.map((quote) => (
                <Link
                  key={quote.id}
                  href={`/portal/quotes/${quote.id}`}
                  className="flex items-center justify-between rounded-md border p-3 hover:bg-muted/40"
                >
                  <div>
                    <p className="font-medium">{quote.number}</p>
                    <p className="text-xs text-muted-foreground">
                      Valid until {quote.valid_until}
                    </p>
                  </div>
                  <div className="text-right">
                    <Badge variant="outline" className="mb-1">
                      {quote.status}
                    </Badge>
                    <p className="text-sm">
                      <CurrencyAmount
                        amount={Number(quote.total || 0)}
                        currency={quote.currency || currency}
                      />
                    </p>
                  </div>
                </Link>
              ))
            )}
          </CardContent>
        </Card>
      </div>

      <Card>
        <CardHeader>
          <CardTitle className="text-base">Payment history</CardTitle>
        </CardHeader>
        <CardContent>
          {(data.recent_payments || []).length === 0 ? (
            <p className="text-sm text-muted-foreground">
              No payments recorded from the portal yet.
            </p>
          ) : (
            <div className="space-y-2">
              {data.recent_payments?.map((payment) => (
                <div
                  key={payment.id}
                  className="flex items-center justify-between rounded-md border p-3"
                >
                  <div>
                    <p className="font-medium">Invoice {payment.invoice_id}</p>
                    <p className="text-xs text-muted-foreground">
                      {payment.paid_at
                        ? `Paid at ${new Date(payment.paid_at).toLocaleString()}`
                        : `Created at ${new Date(
                            payment.created_at || new Date().toISOString()
                          ).toLocaleString()}`}
                    </p>
                  </div>
                  <div className="text-right">
                    <Badge variant="outline" className="mb-1">
                      {payment.status}
                    </Badge>
                    <p className="text-sm">
                      <CurrencyAmount
                        amount={Number(payment.amount || 0)}
                        currency={payment.currency || currency}
                      />
                    </p>
                  </div>
                </div>
              ))}
            </div>
          )}
        </CardContent>
      </Card>
    </div>
  );
}

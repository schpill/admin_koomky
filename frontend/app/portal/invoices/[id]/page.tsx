"use client";

import { useEffect, useMemo, useState } from "react";
import Link from "next/link";
import { useParams } from "next/navigation";
import { Download, Wallet } from "lucide-react";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { CurrencyAmount } from "@/components/shared/currency-amount";
import { portalApiClient, getPortalSession } from "@/lib/portal";

interface InvoiceLineItem {
  id?: string;
  description: string;
  quantity: number;
  unit_price: number;
  vat_rate: number;
  total?: number;
}

interface PortalInvoiceDetail {
  id: string;
  number: string;
  status: string;
  issue_date: string;
  due_date: string;
  subtotal: number;
  tax_amount: number;
  total: number;
  amount_paid: number;
  balance_due: number;
  currency: string;
  line_items?: InvoiceLineItem[];
}

export default function PortalInvoiceDetailPage() {
  const params = useParams<{ id: string }>();
  const invoiceId = params.id;
  const [invoice, setInvoice] = useState<PortalInvoiceDetail | null>(null);
  const [isLoading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    if (!invoiceId) {
      return;
    }

    portalApiClient
      .get<PortalInvoiceDetail>(`/portal/invoices/${invoiceId}`)
      .then((response) => {
        setInvoice(response.data);
        setError(null);
      })
      .catch((err) => setError((err as Error).message))
      .finally(() => setLoading(false));
  }, [invoiceId]);

  const canPay = useMemo(() => {
    if (!invoice) {
      return false;
    }

    return ["sent", "viewed", "partially_paid", "overdue"].includes(
      invoice.status
    );
  }, [invoice]);

  const openPdf = () => {
    const session = getPortalSession();
    if (!session?.portal_token || !invoiceId) {
      return;
    }

    const base = process.env.NEXT_PUBLIC_API_URL || "http://localhost/api/v1";
    fetch(`${base}/portal/invoices/${invoiceId}/pdf`, {
      headers: {
        Authorization: `Bearer ${session.portal_token}`,
      },
    })
      .then((response) => {
        if (!response.ok) {
          throw new Error("Unable to download PDF");
        }

        return response.blob();
      })
      .then((blob) => {
        const url = URL.createObjectURL(blob);
        window.open(url, "_blank", "noopener,noreferrer");
        window.setTimeout(() => URL.revokeObjectURL(url), 2000);
      })
      .catch((err) => setError((err as Error).message));
  };

  if (isLoading) {
    return <p className="text-sm text-muted-foreground">Loading invoice...</p>;
  }

  if (!invoice || error) {
    return (
      <p className="text-sm text-destructive">
        {error || "Invoice not found."}
      </p>
    );
  }

  return (
    <div className="space-y-4">
      <div className="flex flex-wrap items-center justify-between gap-2">
        <h1 className="text-2xl font-bold">{invoice.number}</h1>
        <div className="flex gap-2">
          <Badge variant="outline">{invoice.status}</Badge>
          <Button type="button" variant="outline" size="sm" onClick={openPdf}>
            <Download className="mr-2 h-4 w-4" />
            PDF
          </Button>
          {canPay ? (
            <Button asChild size="sm">
              <Link href={`/portal/invoices/${invoice.id}/pay`}>
                <Wallet className="mr-2 h-4 w-4" />
                Pay now
              </Link>
            </Button>
          ) : null}
        </div>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Invoice summary</CardTitle>
        </CardHeader>
        <CardContent className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
          <div>
            <p className="text-xs text-muted-foreground">Issue date</p>
            <p className="font-medium">{invoice.issue_date}</p>
          </div>
          <div>
            <p className="text-xs text-muted-foreground">Due date</p>
            <p className="font-medium">{invoice.due_date}</p>
          </div>
          <div>
            <p className="text-xs text-muted-foreground">Total</p>
            <p className="font-medium">
              <CurrencyAmount
                amount={Number(invoice.total || 0)}
                currency={invoice.currency || "EUR"}
              />
            </p>
          </div>
          <div>
            <p className="text-xs text-muted-foreground">Balance due</p>
            <p className="font-medium">
              <CurrencyAmount
                amount={Number(invoice.balance_due || 0)}
                currency={invoice.currency || "EUR"}
              />
            </p>
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>Line items</CardTitle>
        </CardHeader>
        <CardContent>
          {(invoice.line_items || []).length === 0 ? (
            <p className="text-sm text-muted-foreground">No line items.</p>
          ) : (
            <div className="overflow-x-auto">
              <table className="w-full text-sm">
                <thead>
                  <tr className="border-b text-left">
                    <th className="pb-2">Description</th>
                    <th className="pb-2">Qty</th>
                    <th className="pb-2">Unit</th>
                    <th className="pb-2">VAT</th>
                    <th className="pb-2">Total</th>
                  </tr>
                </thead>
                <tbody>
                  {invoice.line_items?.map((line, index) => (
                    <tr
                      key={`${line.description}-${index}`}
                      className="border-b"
                    >
                      <td className="py-2">{line.description}</td>
                      <td className="py-2">
                        {Number(line.quantity).toFixed(2)}
                      </td>
                      <td className="py-2">
                        {Number(line.unit_price).toFixed(2)}
                      </td>
                      <td className="py-2">
                        {Number(line.vat_rate).toFixed(2)}%
                      </td>
                      <td className="py-2">
                        <CurrencyAmount
                          amount={Number(
                            line.total ?? line.quantity * line.unit_price
                          )}
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
    </div>
  );
}

"use client";

import { FormEvent, useEffect, useState } from "react";
import { useParams } from "next/navigation";
import { Download } from "lucide-react";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { CurrencyAmount } from "@/components/shared/currency-amount";
import { getPortalSession, portalApiClient } from "@/lib/portal";

interface QuoteLineItem {
  description: string;
  quantity: number;
  unit_price: number;
  vat_rate: number;
  total?: number;
}

interface PortalQuoteDetail {
  id: string;
  number: string;
  status: string;
  issue_date: string;
  valid_until: string;
  subtotal: number;
  tax_amount: number;
  total: number;
  currency: string;
  line_items?: QuoteLineItem[];
}

export default function PortalQuoteDetailPage() {
  const params = useParams<{ id: string }>();
  const quoteId = params.id;

  const [quote, setQuote] = useState<PortalQuoteDetail | null>(null);
  const [isLoading, setLoading] = useState(true);
  const [reason, setReason] = useState("");
  const [error, setError] = useState<string | null>(null);

  const loadQuote = () => {
    if (!quoteId) {
      return;
    }

    setLoading(true);
    portalApiClient
      .get<PortalQuoteDetail>(`/portal/quotes/${quoteId}`)
      .then((response) => {
        setQuote(response.data);
        setError(null);
      })
      .catch((err) => setError((err as Error).message))
      .finally(() => setLoading(false));
  };

  useEffect(() => {
    loadQuote();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [quoteId]);

  const accept = async () => {
    if (!quote) {
      return;
    }

    try {
      await portalApiClient.post(`/portal/quotes/${quote.id}/accept`);
      await loadQuote();
    } catch (err) {
      setError((err as Error).message);
    }
  };

  const reject = async (event: FormEvent) => {
    event.preventDefault();

    if (!quote) {
      return;
    }

    try {
      await portalApiClient.post(`/portal/quotes/${quote.id}/reject`, {
        reason: reason || undefined,
      });
      setReason("");
      await loadQuote();
    } catch (err) {
      setError((err as Error).message);
    }
  };

  const downloadPdf = async () => {
    const session = getPortalSession();
    if (!session?.portal_token || !quoteId) {
      return;
    }

    const base = process.env.NEXT_PUBLIC_API_URL || "http://localhost/api/v1";
    try {
      const response = await fetch(`${base}/portal/quotes/${quoteId}/pdf`, {
        headers: {
          Authorization: `Bearer ${session.portal_token}`,
        },
      });

      if (!response.ok) {
        throw new Error("Unable to download quote PDF");
      }

      const blob = await response.blob();
      const url = URL.createObjectURL(blob);
      window.open(url, "_blank", "noopener,noreferrer");
      window.setTimeout(() => URL.revokeObjectURL(url), 2000);
    } catch (err) {
      setError((err as Error).message);
    }
  };

  if (isLoading) {
    return <p className="text-sm text-muted-foreground">Loading quote...</p>;
  }

  if (!quote || error) {
    return (
      <p className="text-sm text-destructive">{error || "Quote not found."}</p>
    );
  }

  const actionable = ["sent"].includes(quote.status);

  return (
    <div className="space-y-4">
      <div className="flex flex-wrap items-center justify-between gap-2">
        <h1 className="text-2xl font-bold">{quote.number}</h1>
        <div className="flex items-center gap-2">
          <Badge variant="outline">{quote.status}</Badge>
          <Button variant="outline" size="sm" onClick={downloadPdf}>
            <Download className="mr-2 h-4 w-4" />
            PDF
          </Button>
        </div>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Quote summary</CardTitle>
        </CardHeader>
        <CardContent className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
          <div>
            <p className="text-xs text-muted-foreground">Issue date</p>
            <p className="font-medium">{quote.issue_date}</p>
          </div>
          <div>
            <p className="text-xs text-muted-foreground">Valid until</p>
            <p className="font-medium">{quote.valid_until}</p>
          </div>
          <div>
            <p className="text-xs text-muted-foreground">Subtotal</p>
            <p className="font-medium">
              <CurrencyAmount
                amount={Number(quote.subtotal || 0)}
                currency={quote.currency || "EUR"}
              />
            </p>
          </div>
          <div>
            <p className="text-xs text-muted-foreground">Total</p>
            <p className="font-medium">
              <CurrencyAmount
                amount={Number(quote.total || 0)}
                currency={quote.currency || "EUR"}
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
          {(quote.line_items || []).length === 0 ? (
            <p className="text-sm text-muted-foreground">No line items.</p>
          ) : (
            <div className="overflow-x-auto">
              <table className="w-full text-sm">
                <thead>
                  <tr className="border-b text-left">
                    <th className="pb-2">Description</th>
                    <th className="pb-2">Qty</th>
                    <th className="pb-2">Unit</th>
                    <th className="pb-2">Total</th>
                  </tr>
                </thead>
                <tbody>
                  {quote.line_items?.map((line, index) => (
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
                        <CurrencyAmount
                          amount={Number(
                            line.total ?? line.quantity * line.unit_price
                          )}
                          currency={quote.currency || "EUR"}
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

      {actionable ? (
        <Card>
          <CardHeader>
            <CardTitle>Respond to quote</CardTitle>
          </CardHeader>
          <CardContent className="space-y-3">
            <div className="flex flex-wrap gap-2">
              <Button onClick={accept}>Accept quote</Button>
            </div>
            <form className="space-y-2" onSubmit={reject}>
              <Input
                placeholder="Optional rejection reason"
                value={reason}
                onChange={(event) => setReason(event.target.value)}
              />
              <Button type="submit" variant="outline">
                Reject quote
              </Button>
            </form>
          </CardContent>
        </Card>
      ) : null}
    </div>
  );
}

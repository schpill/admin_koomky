"use client";

import { useEffect, useState } from "react";
import Link from "next/link";
import { AlertCircle } from "lucide-react";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { CurrencyAmount } from "@/components/shared/currency-amount";
import { portalApiClient } from "@/lib/portal";

interface PortalQuote {
  id: string;
  number: string;
  issue_date: string;
  valid_until: string;
  status: string;
  total: number;
  currency: string;
}

interface PortalQuoteListResponse {
  data: PortalQuote[];
}

export default function PortalQuotesPage() {
  const [quotes, setQuotes] = useState<PortalQuote[]>([]);
  const [isLoading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    portalApiClient
      .get<PortalQuoteListResponse>("/portal/quotes")
      .then((response) => {
        setQuotes(response.data?.data || []);
        setError(null);
      })
      .catch((err) => setError((err as Error).message))
      .finally(() => setLoading(false));
  }, []);

  return (
    <Card>
      <CardHeader>
        <CardTitle>Quotes</CardTitle>
      </CardHeader>
      <CardContent>
        {isLoading ? (
          <p className="text-sm text-muted-foreground">Loading quotes...</p>
        ) : error ? (
          <p className="inline-flex items-center gap-2 text-sm text-destructive">
            <AlertCircle className="h-4 w-4" />
            {error}
          </p>
        ) : quotes.length === 0 ? (
          <p className="text-sm text-muted-foreground">
            No quotes available in your portal.
          </p>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-sm">
              <thead>
                <tr className="border-b text-left">
                  <th className="pb-3">Number</th>
                  <th className="pb-3">Issue date</th>
                  <th className="pb-3">Valid until</th>
                  <th className="pb-3">Status</th>
                  <th className="pb-3">Total</th>
                </tr>
              </thead>
              <tbody>
                {quotes.map((quote) => (
                  <tr key={quote.id} className="border-b last:border-0">
                    <td className="py-3">
                      <Link
                        href={`/portal/quotes/${quote.id}`}
                        className="font-medium text-primary hover:underline"
                      >
                        {quote.number}
                      </Link>
                    </td>
                    <td className="py-3 text-muted-foreground">
                      {quote.issue_date}
                    </td>
                    <td className="py-3 text-muted-foreground">
                      {quote.valid_until}
                    </td>
                    <td className="py-3">
                      <Badge variant="outline">{quote.status}</Badge>
                    </td>
                    <td className="py-3">
                      <CurrencyAmount
                        amount={Number(quote.total || 0)}
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
  );
}

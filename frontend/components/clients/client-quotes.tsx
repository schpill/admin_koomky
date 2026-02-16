"use client";

import Link from "next/link";
import { useEffect } from "react";
import { EmptyState } from "@/components/ui/empty-state";
import { FileText } from "lucide-react";
import { useQuoteStore } from "@/lib/stores/quotes";
import { QuoteStatusBadge } from "@/components/quotes/quote-status-badge";

interface ClientQuotesProps {
  clientId: string;
}

export function ClientQuotes({ clientId }: ClientQuotesProps) {
  const { quotes, isLoading, fetchQuotes } = useQuoteStore();

  useEffect(() => {
    fetchQuotes({ client_id: clientId, per_page: 50 });
  }, [clientId, fetchQuotes]);

  if (isLoading && quotes.length === 0) {
    return <p className="text-sm text-muted-foreground">Loading quotes...</p>;
  }

  if (quotes.length === 0) {
    return (
      <EmptyState
        icon={<FileText className="h-12 w-12" />}
        title="No quotes for this client"
        description="Quotes linked to this client will appear here."
      />
    );
  }

  return (
    <div className="overflow-x-auto">
      <table className="w-full text-sm">
        <thead>
          <tr className="border-b text-left">
            <th className="pb-3 font-medium text-muted-foreground">Number</th>
            <th className="pb-3 font-medium text-muted-foreground">
              Issue date
            </th>
            <th className="pb-3 font-medium text-muted-foreground">
              Valid until
            </th>
            <th className="pb-3 font-medium text-muted-foreground">Total</th>
            <th className="pb-3 font-medium text-muted-foreground">Status</th>
          </tr>
        </thead>
        <tbody>
          {quotes.map((quote) => (
            <tr key={quote.id} className="border-b last:border-0">
              <td className="py-3">
                <Link
                  href={`/quotes/${quote.id}`}
                  className="font-medium text-primary hover:underline"
                >
                  {quote.number}
                </Link>
              </td>
              <td className="py-3 text-muted-foreground">{quote.issue_date}</td>
              <td className="py-3 text-muted-foreground">
                {quote.valid_until}
              </td>
              <td className="py-3">
                {Number(quote.total || 0).toFixed(2)} EUR
              </td>
              <td className="py-3">
                <QuoteStatusBadge status={quote.status} />
              </td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}

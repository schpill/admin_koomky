"use client";

import { useEffect, useMemo, useState } from "react";
import Link from "next/link";
import { useParams } from "next/navigation";
import { AlertCircle } from "lucide-react";
import { Elements } from "@stripe/react-stripe-js";
import { loadStripe } from "@stripe/stripe-js";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { CurrencyAmount } from "@/components/shared/currency-amount";
import { PaymentForm } from "@/components/portal/payment-form";
import { portalApiClient } from "@/lib/portal";

interface PortalInvoiceSummary {
  id: string;
  number: string;
  due_date: string;
  total: number;
  balance_due: number;
  currency: string;
}

interface PayIntentResponse {
  id: string;
  amount: number;
  currency: string;
  client_secret: string;
}

const stripePublishableKey = process.env.NEXT_PUBLIC_STRIPE_PUBLISHABLE_KEY;
const stripePromise = stripePublishableKey
  ? loadStripe(stripePublishableKey)
  : null;

export default function PortalInvoicePayPage() {
  const params = useParams<{ id: string }>();
  const invoiceId = params.id;

  const [invoice, setInvoice] = useState<PortalInvoiceSummary | null>(null);
  const [intent, setIntent] = useState<PayIntentResponse | null>(null);
  const [isLoading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  const amount = useMemo(() => {
    if (!intent) {
      return Number(invoice?.balance_due || 0);
    }

    return Number(intent.amount || 0);
  }, [intent, invoice?.balance_due]);

  useEffect(() => {
    if (!invoiceId) {
      return;
    }

    const load = async () => {
      try {
        const [invoiceResponse, intentResponse] = await Promise.all([
          portalApiClient.get<PortalInvoiceSummary>(`/portal/invoices/${invoiceId}`),
          portalApiClient.post<PayIntentResponse>(`/portal/invoices/${invoiceId}/pay`),
        ]);

        setInvoice(invoiceResponse.data);
        setIntent(intentResponse.data);
      } catch (err) {
        setError((err as Error).message || "Unable to initialize payment.");
      } finally {
        setLoading(false);
      }
    };

    load();
  }, [invoiceId]);

  if (isLoading) {
    return <p className="text-sm text-muted-foreground">Preparing payment...</p>;
  }

  if (error || !invoice || !intent) {
    return (
      <div className="rounded-lg border border-destructive/30 bg-destructive/5 p-4 text-sm text-destructive">
        <p className="inline-flex items-center gap-2">
          <AlertCircle className="h-4 w-4" />
          {error || "Unable to initialize payment."}
        </p>
      </div>
    );
  }

  return (
    <div className="space-y-4">
      <Card>
        <CardHeader>
          <CardTitle>Pay invoice {invoice.number}</CardTitle>
        </CardHeader>
        <CardContent className="space-y-3">
          <p className="text-sm text-muted-foreground">Due date: {invoice.due_date}</p>
          <p className="text-sm text-muted-foreground">
            Balance due:{" "}
            <span className="font-medium text-foreground">
              <CurrencyAmount amount={Number(invoice.balance_due || 0)} currency={invoice.currency || "EUR"} />
            </span>
          </p>

          {!stripePromise ? (
            <div className="rounded-md border border-amber-300 bg-amber-50 p-3 text-sm text-amber-700">
              Stripe publishable key is missing (`NEXT_PUBLIC_STRIPE_PUBLISHABLE_KEY`).
            </div>
          ) : (
            <Elements stripe={stripePromise}>
              <PaymentForm
                amount={amount}
                currency={intent.currency || invoice.currency || "EUR"}
                clientSecret={intent.client_secret}
                onSuccess={async () => {
                  try {
                    await portalApiClient.get(
                      `/portal/invoices/${invoiceId}/payment-status`
                    );
                  } catch {
                    // Payment status may still be syncing.
                  }
                }}
                onFailure={(reason) => setError(reason)}
              />
            </Elements>
          )}

          <Button asChild variant="outline" className="w-full">
            <Link href={`/portal/invoices/${invoice.id}`}>Back to invoice</Link>
          </Button>
        </CardContent>
      </Card>
    </div>
  );
}

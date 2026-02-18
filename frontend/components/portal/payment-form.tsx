"use client";

import { FormEvent, useMemo, useState } from "react";
import { AlertCircle, CheckCircle2 } from "lucide-react";
import { CardElement, useElements, useStripe } from "@stripe/react-stripe-js";
import { Button } from "@/components/ui/button";
import { CurrencyAmount } from "@/components/shared/currency-amount";

interface PaymentFormProps {
  amount: number;
  currency: string;
  clientSecret: string;
  onSuccess?: () => Promise<void> | void;
  onFailure?: (reason: string) => void;
}

export function PaymentForm({
  amount,
  currency,
  clientSecret,
  onSuccess,
  onFailure,
}: PaymentFormProps) {
  const stripe = useStripe();
  const elements = useElements();
  const [isProcessing, setProcessing] = useState(false);
  const [status, setStatus] = useState<"idle" | "success" | "error">("idle");
  const [message, setMessage] = useState<string | null>(null);

  const isReady = useMemo(
    () => Boolean(stripe && elements),
    [stripe, elements]
  );

  const submit = async (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();

    if (!stripe || !elements) {
      return;
    }

    const cardElement = elements.getElement(CardElement);
    if (!cardElement) {
      return;
    }

    setProcessing(true);
    setMessage(null);

    const result = await stripe.confirmCardPayment(clientSecret, {
      payment_method: {
        card: cardElement,
      },
    });

    if (result.error) {
      const reason = result.error.message || "Payment failed.";
      setStatus("error");
      setMessage(reason);
      onFailure?.(reason);
      setProcessing(false);
      return;
    }

    if (result.paymentIntent?.status === "succeeded") {
      setStatus("success");
      setMessage("Payment succeeded.");
      await onSuccess?.();
    } else {
      const reason = `Payment status: ${result.paymentIntent?.status || "unknown"}`;
      setStatus("error");
      setMessage(reason);
      onFailure?.(reason);
    }

    setProcessing(false);
  };

  return (
    <form className="space-y-4" onSubmit={submit}>
      <div className="rounded-lg border bg-muted/20 p-3 text-sm">
        <p className="font-medium">Amount to pay</p>
        <p className="text-lg font-semibold">
          <CurrencyAmount
            amount={Number(amount || 0)}
            currency={currency || "EUR"}
          />
        </p>
      </div>

      <div className="rounded-lg border bg-background p-3">
        <CardElement
          options={{
            hidePostalCode: true,
            style: {
              base: {
                fontSize: "16px",
                color: "#1f2937",
                "::placeholder": {
                  color: "#9ca3af",
                },
              },
            },
          }}
        />
      </div>

      <Button
        type="submit"
        className="w-full"
        disabled={!isReady || isProcessing}
      >
        {isProcessing ? "Processing payment..." : "Pay now"}
      </Button>

      {status !== "idle" && message ? (
        <p
          className={`inline-flex items-center gap-2 text-sm ${
            status === "success" ? "text-emerald-600" : "text-destructive"
          }`}
        >
          {status === "success" ? (
            <CheckCircle2 className="h-4 w-4" />
          ) : (
            <AlertCircle className="h-4 w-4" />
          )}
          {message}
        </p>
      ) : null}
    </form>
  );
}

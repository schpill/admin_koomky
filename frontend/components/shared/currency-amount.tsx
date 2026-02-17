import { cn } from "@/lib/utils";
import { formatCurrency, type CurrencyLike } from "@/lib/currency";

interface CurrencyAmountProps {
  amount: number;
  currency: string;
  locale?: string;
  currencies?: CurrencyLike[];
  className?: string;
}

export function CurrencyAmount({
  amount,
  currency,
  locale = "en-US",
  currencies = [],
  className,
}: CurrencyAmountProps) {
  return (
    <span className={cn(className)}>
      {formatCurrency(amount, currency, locale, currencies)}
    </span>
  );
}

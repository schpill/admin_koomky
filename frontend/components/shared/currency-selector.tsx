"use client";

import { useMemo, useState } from "react";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { currencyFlag, defaultCurrencyList, type CurrencyLike } from "@/lib/currency";

interface CurrencySelectorProps {
  id: string;
  label: string;
  value: string;
  onValueChange: (value: string) => void;
  currencies?: CurrencyLike[];
  disabled?: boolean;
}

export function CurrencySelector({
  id,
  label,
  value,
  onValueChange,
  currencies = [],
  disabled = false,
}: CurrencySelectorProps) {
  const [query, setQuery] = useState("");

  const normalized = useMemo(() => {
    const source = currencies.length > 0 ? currencies : defaultCurrencyList();
    return source
      .filter((currency) => currency.is_active !== false)
      .map((currency) => ({
        code: String(currency.code || "").toUpperCase(),
        name: String(currency.name || "").trim(),
        symbol: String(currency.symbol || "").trim(),
        decimal_places:
          typeof currency.decimal_places === "number"
            ? currency.decimal_places
            : 2,
      }))
      .sort((a, b) => a.code.localeCompare(b.code));
  }, [currencies]);

  const filtered = useMemo(() => {
    const term = query.trim().toLowerCase();
    if (!term) {
      return normalized;
    }

    return normalized.filter((currency) => {
      const text = `${currency.code} ${currency.name} ${currency.symbol}`.toLowerCase();
      return text.includes(term);
    });
  }, [normalized, query]);

  const options = filtered.length > 0 ? filtered : normalized;

  return (
    <div className="space-y-2">
      <Label htmlFor={`${id}-search`}>Search currency</Label>
      <Input
        id={`${id}-search`}
        aria-label="Search currency"
        value={query}
        onChange={(event) => setQuery(event.target.value)}
        placeholder="Code, name or symbol"
        disabled={disabled}
      />

      <Label htmlFor={id}>{label}</Label>
      <select
        id={id}
        aria-label={label}
        className="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
        value={String(value || "").toUpperCase()}
        onChange={(event) => onValueChange(event.target.value.toUpperCase())}
        disabled={disabled}
      >
        {options.map((currency) => (
          <option key={currency.code} value={currency.code}>
            {`${currencyFlag(currency.code)} ${currency.code} - ${currency.name}`}
          </option>
        ))}
      </select>
    </div>
  );
}

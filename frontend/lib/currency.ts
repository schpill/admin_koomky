export interface CurrencyLike {
  code: string;
  name?: string;
  symbol?: string;
  decimal_places?: number;
  is_active?: boolean;
}

const FALLBACK_CURRENCIES: Record<
  string,
  { name: string; symbol: string; decimal_places: number; flag: string }
> = {
  EUR: { name: "Euro", symbol: "EUR", decimal_places: 2, flag: "🇪🇺" },
  USD: { name: "US Dollar", symbol: "USD", decimal_places: 2, flag: "🇺🇸" },
  GBP: { name: "British Pound", symbol: "GBP", decimal_places: 2, flag: "🇬🇧" },
  CHF: { name: "Swiss Franc", symbol: "CHF", decimal_places: 2, flag: "🇨🇭" },
  CAD: {
    name: "Canadian Dollar",
    symbol: "CAD",
    decimal_places: 2,
    flag: "🇨🇦",
  },
  JPY: { name: "Japanese Yen", symbol: "JPY", decimal_places: 0, flag: "🇯🇵" },
  AUD: {
    name: "Australian Dollar",
    symbol: "AUD",
    decimal_places: 2,
    flag: "🇦🇺",
  },
  SEK: { name: "Swedish Krona", symbol: "SEK", decimal_places: 2, flag: "🇸🇪" },
  NOK: {
    name: "Norwegian Krone",
    symbol: "NOK",
    decimal_places: 2,
    flag: "🇳🇴",
  },
  DKK: { name: "Danish Krone", symbol: "DKK", decimal_places: 2, flag: "🇩🇰" },
  PLN: { name: "Polish Zloty", symbol: "PLN", decimal_places: 2, flag: "🇵🇱" },
  CZK: { name: "Czech Koruna", symbol: "CZK", decimal_places: 2, flag: "🇨🇿" },
  HUF: {
    name: "Hungarian Forint",
    symbol: "HUF",
    decimal_places: 2,
    flag: "🇭🇺",
  },
  BRL: { name: "Brazilian Real", symbol: "BRL", decimal_places: 2, flag: "🇧🇷" },
  CNY: { name: "Chinese Yuan", symbol: "CNY", decimal_places: 2, flag: "🇨🇳" },
};

export function currencyFlag(code: string): string {
  const normalized = code.toUpperCase();
  return FALLBACK_CURRENCIES[normalized]?.flag ?? "🏳️";
}

export function currencyMeta(
  code: string,
  available: CurrencyLike[] = []
): CurrencyLike {
  const normalized = code.toUpperCase();
  const fromList = available.find(
    (currency) => currency.code.toUpperCase() === normalized
  );
  if (fromList) {
    return fromList;
  }

  const fallback = FALLBACK_CURRENCIES[normalized];
  if (fallback) {
    return { code: normalized, ...fallback };
  }

  return {
    code: normalized,
    name: normalized,
    symbol: normalized,
    decimal_places: 2,
  };
}

export function formatCurrency(
  amount: number,
  currency: string,
  locale = "en-US",
  available: CurrencyLike[] = []
): string {
  const normalized = currency.toUpperCase();
  const meta = currencyMeta(normalized, available);
  const decimals = Math.max(0, Math.min(6, meta.decimal_places ?? 2));

  try {
    return new Intl.NumberFormat(locale, {
      style: "currency",
      currency: normalized,
      minimumFractionDigits: decimals,
      maximumFractionDigits: decimals,
    }).format(Number(amount || 0));
  } catch {
    return `${Number(amount || 0).toFixed(decimals)} ${normalized}`;
  }
}

export function defaultCurrencyList(): CurrencyLike[] {
  return Object.entries(FALLBACK_CURRENCIES).map(([code, info]) => ({
    code,
    name: info.name,
    symbol: info.symbol,
    decimal_places: info.decimal_places,
    is_active: true,
  }));
}

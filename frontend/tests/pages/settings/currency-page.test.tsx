import { beforeEach, describe, expect, it, vi } from "vitest";
import { fireEvent, render, screen, waitFor } from "@testing-library/react";
import { I18nProvider } from "@/components/providers/i18n-provider";

const toastSuccess = vi.fn();
const toastError = vi.fn();
const useCurrencyStoreMock = vi.fn();

vi.mock("sonner", () => ({
  toast: {
    success: (...args: unknown[]) => toastSuccess(...args),
    error: (...args: unknown[]) => toastError(...args),
  },
}));

vi.mock("@/lib/stores/currencies", () => ({
  useCurrencyStore: () => useCurrencyStoreMock(),
}));

vi.mock("@/components/shared/currency-selector", () => ({
  CurrencySelector: ({
    label,
    value,
    currencies,
    onValueChange,
  }: {
    label: string;
    value: string;
    currencies: Array<{ code: string }>;
    onValueChange: (value: string) => void;
  }) => (
    <label>
      {label}
      <select
        aria-label={label}
        value={value}
        onChange={(event) => onValueChange(event.target.value)}
      >
        {currencies.map((currency) => (
          <option key={currency.code} value={currency.code}>
            {currency.code}
          </option>
        ))}
      </select>
    </label>
  ),
}));

vi.mock("@/components/shared/currency-amount", () => ({
  CurrencyAmount: ({
    amount,
    currency,
  }: {
    amount: number;
    currency: string;
  }) => (
    <span>
      {amount} {currency}
    </span>
  ),
}));

import CurrencySettingsPage from "@/app/(dashboard)/settings/currency/page";

describe("CurrencySettingsPage", () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it("loads settings and saves selected provider/base currency", async () => {
    const fetchCurrencies = vi.fn().mockResolvedValue(undefined);
    const fetchRates = vi.fn().mockResolvedValue(undefined);
    const updateCurrencySettings = vi.fn().mockResolvedValue(undefined);

    useCurrencyStoreMock.mockReturnValue({
      currencies: [
        {
          code: "EUR",
          name: "Euro",
          symbol: "€",
          decimal_places: 2,
          is_active: true,
        },
        {
          code: "USD",
          name: "US Dollar",
          symbol: "$",
          decimal_places: 2,
          is_active: true,
        },
      ],
      rates: { USD: 1.12 },
      baseCurrency: "USD",
      exchangeRateProvider: "ecb",
      isLoading: false,
      fetchCurrencies,
      fetchRates,
      updateCurrencySettings,
    });

    render(
      <I18nProvider initialLocale="en">
        <CurrencySettingsPage />
      </I18nProvider>
    );

    await waitFor(() => {
      expect(fetchCurrencies).toHaveBeenCalledTimes(1);
      expect(fetchRates).toHaveBeenCalledTimes(1);
    });

    fireEvent.change(screen.getByLabelText("Rate provider"), {
      target: { value: "open_exchange_rates" },
    });

    fireEvent.change(screen.getByLabelText("Base currency"), {
      target: { value: "EUR" },
    });

    fireEvent.click(
      screen.getByRole("button", { name: "Save currency settings" })
    );

    await waitFor(() => {
      expect(updateCurrencySettings).toHaveBeenCalledWith(
        "EUR",
        "open_exchange_rates"
      );
      expect(fetchRates).toHaveBeenCalledWith("EUR");
      expect(toastSuccess).toHaveBeenCalledWith("Currency settings updated");
    });

    fireEvent.change(screen.getByLabelText("Rate EUR to USD"), {
      target: { value: "1.5" },
    });

    expect(screen.getByText("(overridden locally)")).toBeInTheDocument();
  });

  it("shows empty rates state and save errors", async () => {
    useCurrencyStoreMock.mockReturnValue({
      currencies: [
        {
          code: "EUR",
          name: "Euro",
          symbol: "€",
          decimal_places: 2,
          is_active: true,
        },
      ],
      rates: {},
      baseCurrency: "EUR",
      exchangeRateProvider: "open_exchange_rates",
      isLoading: false,
      fetchCurrencies: vi.fn().mockResolvedValue(undefined),
      fetchRates: vi.fn().mockResolvedValue(undefined),
      updateCurrencySettings: vi
        .fn()
        .mockRejectedValue(new Error("settings failed")),
    });

    render(
      <I18nProvider initialLocale="en">
        <CurrencySettingsPage />
      </I18nProvider>
    );

    expect(
      screen.getByText(
        "No rates available yet. Save settings to fetch latest values."
      )
    ).toBeInTheDocument();

    fireEvent.click(
      screen.getByRole("button", { name: "Save currency settings" })
    );

    await waitFor(() => {
      expect(toastError).toHaveBeenCalledWith("settings failed");
    });
  });
});

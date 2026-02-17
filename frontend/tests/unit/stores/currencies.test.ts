import { beforeEach, describe, expect, it, vi } from "vitest";
import { useCurrencyStore } from "@/lib/stores/currencies";

vi.mock("@/lib/api", () => ({
  apiClient: {
    get: vi.fn(),
    put: vi.fn(),
  },
}));

import { apiClient } from "@/lib/api";

describe("useCurrencyStore", () => {
  beforeEach(() => {
    useCurrencyStore.setState({
      currencies: [],
      rates: {},
      baseCurrency: "EUR",
      exchangeRateProvider: "open_exchange_rates",
      isLoading: false,
      error: null,
    });
    vi.clearAllMocks();
  });

  it("fetches currencies and rates", async () => {
    (apiClient.get as any).mockResolvedValueOnce({
      data: [
        {
          id: "cur_eur",
          code: "EUR",
          name: "Euro",
          symbol: "EUR",
          decimal_places: 2,
          is_active: true,
        },
        {
          id: "cur_usd",
          code: "USD",
          name: "US Dollar",
          symbol: "USD",
          decimal_places: 2,
          is_active: true,
        },
      ],
    });

    (apiClient.get as any).mockResolvedValueOnce({
      data: {
        base_currency: "EUR",
        rates: {
          USD: 1.1,
          GBP: 0.86,
        },
      },
    });

    await useCurrencyStore.getState().fetchCurrencies();
    await useCurrencyStore.getState().fetchRates("EUR");

    const state = useCurrencyStore.getState();
    expect(state.currencies).toHaveLength(2);
    expect(state.baseCurrency).toBe("EUR");
    expect(state.rates.USD).toBe(1.1);
    expect(apiClient.get).toHaveBeenNthCalledWith(1, "/currencies");
    expect(apiClient.get).toHaveBeenNthCalledWith(2, "/currencies/rates", {
      params: { base: "EUR" },
    });
  });

  it("updates base currency settings", async () => {
    (apiClient.put as any).mockResolvedValue({
      data: {
        id: "usr_1",
        base_currency: "USD",
        exchange_rate_provider: "ecb",
      },
    });

    await useCurrencyStore.getState().updateCurrencySettings("USD", "ecb");

    const state = useCurrencyStore.getState();
    expect(state.baseCurrency).toBe("USD");
    expect(state.exchangeRateProvider).toBe("ecb");
    expect(apiClient.put).toHaveBeenCalledWith("/settings/currency", {
      base_currency: "USD",
      exchange_rate_provider: "ecb",
    });
  });

  it("keeps last error when request fails", async () => {
    (apiClient.get as any).mockRejectedValueOnce(
      new Error("currencies failed")
    );
    await useCurrencyStore.getState().fetchCurrencies();
    expect(useCurrencyStore.getState().error).toBe("currencies failed");

    (apiClient.put as any).mockRejectedValueOnce(new Error("settings failed"));
    await expect(
      useCurrencyStore.getState().updateCurrencySettings("JPY")
    ).rejects.toThrow("settings failed");
    expect(useCurrencyStore.getState().error).toBe("settings failed");
  });
});

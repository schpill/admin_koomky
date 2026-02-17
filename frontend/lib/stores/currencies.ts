import { create } from "zustand";
import { apiClient } from "@/lib/api";
import { defaultCurrencyList, type CurrencyLike } from "@/lib/currency";

export interface Currency extends CurrencyLike {
  id?: string;
  name: string;
  symbol: string;
  decimal_places: number;
  is_active: boolean;
}

interface CurrencyState {
  currencies: Currency[];
  rates: Record<string, number>;
  baseCurrency: string;
  exchangeRateProvider: string;
  isLoading: boolean;
  error: string | null;

  fetchCurrencies: () => Promise<void>;
  fetchRates: (baseCurrency?: string) => Promise<void>;
  updateCurrencySettings: (
    baseCurrency: string,
    exchangeRateProvider?: string
  ) => Promise<void>;
}

export const useCurrencyStore = create<CurrencyState>((set, get) => ({
  currencies: defaultCurrencyList() as Currency[],
  rates: {},
  baseCurrency: "EUR",
  exchangeRateProvider: "open_exchange_rates",
  isLoading: false,
  error: null,

  fetchCurrencies: async () => {
    set({ isLoading: true, error: null });
    try {
      const response = await apiClient.get<Currency[]>("/currencies");
      set({
        currencies: response.data || [],
        isLoading: false,
      });
    } catch (error) {
      set({ isLoading: false, error: (error as Error).message });
    }
  },

  fetchRates: async (baseCurrency) => {
    set({ isLoading: true, error: null });
    try {
      const nextBase = (
        baseCurrency ||
        get().baseCurrency ||
        "EUR"
      ).toUpperCase();
      const response = await apiClient.get<{
        base_currency: string;
        rates: Record<string, number>;
      }>("/currencies/rates", {
        params: { base: nextBase },
      });

      set({
        baseCurrency: String(
          response.data?.base_currency || nextBase
        ).toUpperCase(),
        rates: response.data?.rates || {},
        isLoading: false,
      });
    } catch (error) {
      set({ isLoading: false, error: (error as Error).message });
    }
  },

  updateCurrencySettings: async (baseCurrency, exchangeRateProvider) => {
    set({ isLoading: true, error: null });
    try {
      const payload: Record<string, string> = {
        base_currency: baseCurrency.toUpperCase(),
      };

      if (exchangeRateProvider) {
        payload.exchange_rate_provider = exchangeRateProvider;
      }

      const response = await apiClient.put<{
        base_currency?: string;
        exchange_rate_provider?: string;
      }>("/settings/currency", payload);

      const userSettings = response.data || {};
      set({
        baseCurrency: String(
          userSettings.base_currency || payload.base_currency
        ),
        exchangeRateProvider: String(
          userSettings.exchange_rate_provider ||
            exchangeRateProvider ||
            get().exchangeRateProvider
        ),
        isLoading: false,
      });
    } catch (error) {
      set({ isLoading: false, error: (error as Error).message });
      throw error;
    }
  },
}));

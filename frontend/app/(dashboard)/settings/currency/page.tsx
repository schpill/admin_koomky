"use client";

import { useEffect, useMemo, useState } from "react";
import { toast } from "sonner";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { CurrencyAmount } from "@/components/shared/currency-amount";
import { CurrencySelector } from "@/components/shared/currency-selector";
import { useCurrencyStore } from "@/lib/stores/currencies";
import { useI18n } from "@/components/providers/i18n-provider";

export default function CurrencySettingsPage() {
  const { t } = useI18n();
  const {
    currencies,
    rates,
    baseCurrency,
    exchangeRateProvider,
    isLoading,
    fetchCurrencies,
    fetchRates,
    updateCurrencySettings,
  } = useCurrencyStore();

  const [selectedBaseCurrency, setSelectedBaseCurrency] = useState("EUR");
  const [selectedProvider, setSelectedProvider] = useState(
    "open_exchange_rates"
  );
  const [manualOverrides, setManualOverrides] = useState<
    Record<string, string>
  >({});

  useEffect(() => {
    fetchCurrencies();
    fetchRates();
  }, [fetchCurrencies, fetchRates]);

  useEffect(() => {
    setSelectedBaseCurrency(baseCurrency || "EUR");
  }, [baseCurrency]);

  useEffect(() => {
    setSelectedProvider(exchangeRateProvider || "open_exchange_rates");
  }, [exchangeRateProvider]);

  const previewRates = useMemo(() => {
    const entries = Object.entries(rates || {}).slice(0, 12);
    return entries.map(([currency, rate]) => ({
      currency,
      rate: Number(manualOverrides[currency] || rate || 0),
      overridden: manualOverrides[currency] !== undefined,
    }));
  }, [manualOverrides, rates]);

  const handleSave = async () => {
    try {
      await updateCurrencySettings(selectedBaseCurrency, selectedProvider);
      await fetchRates(selectedBaseCurrency);
      toast.success(t("settings.currency.toasts.success"));
    } catch (error) {
      toast.error(
        (error as Error).message || t("settings.currency.toasts.failed")
      );
    }
  };

  return (
    <div className="space-y-6">
      <div className="space-y-2">
        <h1 className="text-3xl font-bold">{t("settings.currency.title")}</h1>
        <p className="text-sm text-muted-foreground">
          {t("settings.currency.description")}
        </p>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>{t("settings.currency.baseConfig")}</CardTitle>
        </CardHeader>
        <CardContent className="space-y-4">
          <CurrencySelector
            id="settings-base-currency"
            label={t("settings.currency.baseCurrency")}
            value={selectedBaseCurrency}
            currencies={currencies}
            onValueChange={setSelectedBaseCurrency}
            disabled={isLoading}
          />

          <div className="space-y-2">
            <Label htmlFor="exchange-rate-provider">
              {t("settings.currency.rateProvider")}
            </Label>
            <select
              id="exchange-rate-provider"
              className="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
              value={selectedProvider}
              onChange={(event) => setSelectedProvider(event.target.value)}
              disabled={isLoading}
            >
              <option value="open_exchange_rates">
                {t("settings.currency.openExchangeRates")}
              </option>
              <option value="ecb">{t("settings.currency.ecb")}</option>
            </select>
          </div>

          <div className="flex justify-end">
            <Button onClick={handleSave} disabled={isLoading}>
              {isLoading
                ? t("settings.currency.saving")
                : t("settings.currency.saveSettings")}
            </Button>
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>{t("settings.currency.manualOverrides")}</CardTitle>
        </CardHeader>
        <CardContent className="space-y-3">
          {previewRates.length === 0 ? (
            <p className="text-sm text-muted-foreground">
              {t("settings.currency.noRates")}
            </p>
          ) : (
            previewRates.map(({ currency, rate, overridden }) => (
              <div
                key={currency}
                className="grid items-center gap-3 rounded-md border bg-muted/20 p-3 md:grid-cols-3"
              >
                <p className="text-sm font-medium">
                  {selectedBaseCurrency} → {currency}
                </p>
                <Input
                  value={manualOverrides[currency] ?? String(rate)}
                  onChange={(event) =>
                    setManualOverrides((current) => ({
                      ...current,
                      [currency]: event.target.value,
                    }))
                  }
                  inputMode="decimal"
                  aria-label={`Rate ${selectedBaseCurrency} to ${currency}`}
                />
                <p className="text-sm text-muted-foreground">
                  100 {selectedBaseCurrency} ={" "}
                  <CurrencyAmount
                    amount={
                      100 * Number((manualOverrides[currency] ?? rate) || 0)
                    }
                    currency={currency}
                    currencies={currencies}
                  />{" "}
                  {overridden && (
                    <span className="font-medium text-amber-600">
                      {t("settings.currency.overriddenLocally")}
                    </span>
                  )}
                </p>
              </div>
            ))
          )}
        </CardContent>
      </Card>
    </div>
  );
}

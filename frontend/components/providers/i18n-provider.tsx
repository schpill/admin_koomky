"use client";

import {
  createContext,
  useCallback,
  useContext,
  useEffect,
  useMemo,
  useState,
} from "react";
import {
  defaultLocale,
  isLocale,
  localeCookieName,
  localeStorageKey,
  type Locale,
} from "@/lib/i18n/config";
import { messages, type MessageKey, type MessageValues } from "@/lib/i18n/messages";

interface I18nContextValue {
  locale: Locale;
  setLocale: (locale: Locale) => void;
  t: (key: MessageKey, values?: MessageValues) => string;
}

const I18nContext = createContext<I18nContextValue | null>(null);

interface I18nProviderProps {
  initialLocale: Locale;
  children: React.ReactNode;
}

function resolveMessage(locale: Locale, key: string): string | undefined {
  const segments = key.split(".");
  let current: unknown = messages[locale];

  for (const segment of segments) {
    if (
      current === null ||
      typeof current !== "object" ||
      !(segment in (current as Record<string, unknown>))
    ) {
      return undefined;
    }
    current = (current as Record<string, unknown>)[segment];
  }

  return typeof current === "string" ? current : undefined;
}

function interpolate(template: string, values?: MessageValues): string {
  if (!values) {
    return template;
  }

  return template.replace(/\{(\w+)\}/g, (_, token) => {
    const value = values[token];
    return value === undefined ? `{${token}}` : String(value);
  });
}

export function I18nProvider({ initialLocale, children }: I18nProviderProps) {
  const [locale, setLocale] = useState<Locale>(initialLocale);

  useEffect(() => {
    const storedLocale = window.localStorage.getItem(localeStorageKey);
    if (isLocale(storedLocale)) {
      setLocale(storedLocale);
    }
  }, []);

  useEffect(() => {
    document.documentElement.lang = locale;
    window.localStorage.setItem(localeStorageKey, locale);
    document.cookie = `${localeCookieName}=${locale}; path=/; max-age=31536000; samesite=lax`;
  }, [locale]);

  const t = useCallback(
    (key: MessageKey, values?: MessageValues) => {
      const message =
        resolveMessage(locale, key) ??
        resolveMessage(defaultLocale, key) ??
        key;
      return interpolate(message, values);
    },
    [locale],
  );

  const contextValue = useMemo<I18nContextValue>(
    () => ({
      locale,
      setLocale,
      t,
    }),
    [locale, t],
  );

  return (
    <I18nContext.Provider value={contextValue}>{children}</I18nContext.Provider>
  );
}

export function useI18n(): I18nContextValue {
  const context = useContext(I18nContext);
  if (!context) {
    throw new Error("useI18n must be used within I18nProvider");
  }
  return context;
}

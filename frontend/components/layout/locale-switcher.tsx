"use client";

import { Globe } from "lucide-react";
import { cn } from "@/lib/utils";
import { locales } from "@/lib/i18n/config";
import { useI18n } from "@/components/providers/i18n-provider";

interface LocaleSwitcherProps {
  className?: string;
  compact?: boolean;
}

export function LocaleSwitcher({
  className,
  compact = false,
}: LocaleSwitcherProps) {
  const { locale, setLocale, t } = useI18n();

  return (
    <div
      className={cn(
        "inline-flex items-center gap-1 rounded-md border border-border/70 bg-background/80 p-1 shadow-sm backdrop-blur",
        className
      )}
      aria-label={t("common.language")}
    >
      {!compact && <Globe className="mx-1 h-4 w-4 text-muted-foreground" />}
      {locales.map((candidateLocale) => (
        <button
          key={candidateLocale}
          type="button"
          onClick={() => setLocale(candidateLocale)}
          className={cn(
            "rounded px-2 py-1 text-xs font-semibold transition-colors",
            locale === candidateLocale
              ? "bg-primary text-primary-foreground"
              : "text-muted-foreground hover:bg-accent hover:text-accent-foreground"
          )}
          aria-pressed={locale === candidateLocale}
        >
          {t(`common.languages.${candidateLocale}`)}
        </button>
      ))}
    </div>
  );
}

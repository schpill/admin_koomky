"use client";

import Image from "next/image";
import { cn } from "@/lib/utils";
import { useI18n } from "@/components/providers/i18n-provider";

interface BrandFooterProps {
  className?: string;
  compact?: boolean;
}

export function BrandFooter({ className, compact = false }: BrandFooterProps) {
  const { t } = useI18n();

  return (
    <footer
      className={cn(
        "flex flex-wrap items-center justify-center gap-2 text-xs text-muted-foreground",
        className,
      )}
    >
      <span>{t("brand.footer", { heart: "❤️" })}</span>
      <Image
        src="/brand/logo.png"
        alt="Koomky"
        width={188}
        height={44}
        className={compact ? "h-6 w-auto" : "h-7 w-auto"}
      />
    </footer>
  );
}

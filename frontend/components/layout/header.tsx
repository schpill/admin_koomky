"use client";

import { Bell, Moon, Sun, User } from "lucide-react";
import { useTheme } from "next-themes";
import { Button } from "@/components/ui/button";
import { CommandPalette } from "@/components/search/command-palette";
import { LocaleSwitcher } from "@/components/layout/locale-switcher";
import { useI18n } from "@/components/providers/i18n-provider";

export function Header() {
  const { theme, setTheme } = useTheme();
  const { t } = useI18n();

  return (
    <header className="brand-header flex h-16 items-center justify-between border-b border-border/70 px-6">
      {/* Search */}
      <div className="flex items-center gap-4">
        <CommandPalette />
      </div>

      {/* Right Actions */}
      <div className="flex items-center gap-2">
        <LocaleSwitcher compact />

        <Button
          variant="ghost"
          size="icon"
          onClick={() => setTheme(theme === "dark" ? "light" : "dark")}
          className="brand-control"
        >
          <Sun className="h-5 w-5 rotate-0 scale-100 transition-all dark:-rotate-90 dark:scale-0" />
          <Moon className="absolute h-5 w-5 rotate-90 scale-0 transition-all dark:rotate-0 dark:scale-100" />
          <span className="sr-only">{t("header.toggleTheme")}</span>
        </Button>

        <Button variant="ghost" size="icon" className="brand-control">
          <Bell className="h-5 w-5" />
          <span className="sr-only">{t("header.notifications")}</span>
        </Button>

        <Button variant="ghost" size="icon" className="brand-control">
          <User className="h-5 w-5" />
          <span className="sr-only">{t("header.userMenu")}</span>
        </Button>
      </div>
    </header>
  );
}

"use client";

import { Keyboard, Menu, Moon, Sun, User } from "lucide-react";
import { useTheme } from "next-themes";
import { Button } from "@/components/ui/button";
import { CommandPalette } from "@/components/search/command-palette";
import { LocaleSwitcher } from "@/components/layout/locale-switcher";
import { useI18n } from "@/components/providers/i18n-provider";
import { NotificationBell } from "@/components/layout/notification-bell";

interface HeaderProps {
  onOpenNavigation?: () => void;
  onOpenShortcuts?: () => void;
}

export function Header({ onOpenNavigation, onOpenShortcuts }: HeaderProps) {
  const { theme, setTheme } = useTheme();
  const { t } = useI18n();

  return (
    <header className="brand-header flex h-16 items-center justify-between border-b border-border/70 px-6">
      {/* Search */}
      <div className="flex items-center gap-4">
        <Button
          type="button"
          variant="ghost"
          size="icon"
          className="brand-control md:hidden"
          onClick={onOpenNavigation}
        >
          <Menu className="h-5 w-5" />
          <span className="sr-only">{t("header.openNavigation")}</span>
        </Button>
        <CommandPalette />
      </div>

      {/* Right Actions */}
      <div className="flex items-center gap-2">
        <Button
          type="button"
          variant="ghost"
          size="icon"
          className="brand-control"
          onClick={onOpenShortcuts}
        >
          <Keyboard className="h-5 w-5" />
          <span className="sr-only">{t("header.openShortcuts")}</span>
        </Button>
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

        <NotificationBell />

        <Button variant="ghost" size="icon" className="brand-control">
          <User className="h-5 w-5" />
          <span className="sr-only">{t("header.userMenu")}</span>
        </Button>
      </div>
    </header>
  );
}

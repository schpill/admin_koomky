"use client";

import { useEffect, useState } from "react";
import Link from "next/link";
import { useRouter } from "next/navigation";
import { Keyboard, LogOut, Menu, Moon, Sun, User } from "lucide-react";
import { useTheme } from "next-themes";
import { Button } from "@/components/ui/button";
import { CommandPalette } from "@/components/search/command-palette";
import { LocaleSwitcher } from "@/components/layout/locale-switcher";
import { useI18n } from "@/components/providers/i18n-provider";
import { NotificationBell } from "@/components/layout/notification-bell";
import { TimerBadge } from "@/components/timer/timer-badge";
import { apiClient } from "@/lib/api";
import { useAuthStore } from "@/lib/stores/auth";
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";

interface HeaderProps {
  onOpenNavigation?: () => void;
  onOpenShortcuts?: () => void;
}

export function Header({ onOpenNavigation, onOpenShortcuts }: HeaderProps) {
  const { theme, setTheme, resolvedTheme } = useTheme();
  const { t } = useI18n();
  const router = useRouter();
  const user = useAuthStore((state) => state.user);
  const logout = useAuthStore((state) => state.logout);
  const [mounted, setMounted] = useState(false);
  const [userMenuOpen, setUserMenuOpen] = useState(false);

  useEffect(() => {
    setMounted(true);
  }, []);

  const isDark = mounted && (resolvedTheme === "dark" || theme === "dark");
  const initials =
    user?.name
      ?.split(" ")
      .map((part) => part[0])
      .join("")
      .slice(0, 2)
      .toUpperCase() || "U";

  async function handleLogout() {
    try {
      await apiClient.post("/auth/logout");
    } finally {
      setUserMenuOpen(false);
      logout();
      router.push("/auth/login");
    }
  }

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
          onClick={() => setTheme(isDark ? "light" : "dark")}
          className="brand-control"
        >
          {isDark ? (
            <Sun className="h-5 w-5 transition-all" />
          ) : (
            <Moon className="h-5 w-5 transition-all" />
          )}
          <span className="sr-only">{t("header.toggleTheme")}</span>
        </Button>

        <TimerBadge />
        <NotificationBell />

        <DropdownMenu open={userMenuOpen} onOpenChange={setUserMenuOpen}>
          <DropdownMenuTrigger asChild>
            <Button
              variant="ghost"
              size="icon"
              className="brand-control overflow-hidden rounded-full"
              aria-label={t("header.userMenu")}
              onClick={() => setUserMenuOpen((current) => !current)}
            >
              {user?.avatar_url ? (
                <img
                  src={user.avatar_url}
                  alt={t("header.userMenu")}
                  className="h-full w-full object-cover"
                />
              ) : user ? (
                <span className="text-xs font-semibold">{initials}</span>
              ) : (
                <User className="h-5 w-5" />
              )}
            </Button>
          </DropdownMenuTrigger>
          <DropdownMenuContent align="end" className="w-48">
            <DropdownMenuItem asChild>
              <Link href="/profile">{t("header.myProfile")}</Link>
            </DropdownMenuItem>
            <DropdownMenuSeparator />
            <DropdownMenuItem onClick={handleLogout}>
              <LogOut className="h-4 w-4" />
              {t("header.logout")}
            </DropdownMenuItem>
          </DropdownMenuContent>
        </DropdownMenu>
      </div>
    </header>
  );
}

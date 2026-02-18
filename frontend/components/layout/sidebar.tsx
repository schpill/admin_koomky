"use client";

import Image from "next/image";
import Link from "next/link";
import { usePathname } from "next/navigation";
import { useRouter } from "next/navigation";
import {
  LayoutDashboard,
  Users,
  FileText,
  FolderKanban,
  Megaphone,
  BarChart3,
  CalendarDays,
  Receipt,
  Settings,
  HelpCircle,
  ExternalLink,
  LogOut,
  ChevronLeft,
} from "lucide-react";
import { cn } from "@/lib/utils";
import { Button } from "@/components/ui/button";
import { Separator } from "@/components/ui/separator";
import { useAuthStore } from "@/lib/stores/auth";
import { useI18n } from "@/components/providers/i18n-provider";

const navigation = [
  { key: "dashboard", href: "/", icon: LayoutDashboard },
  { key: "clients", href: "/clients", icon: Users },
  { key: "projects", href: "/projects", icon: FolderKanban },
  { key: "campaigns", href: "/campaigns", icon: Megaphone },
  { key: "invoices", href: "/invoices", icon: FileText },
  { key: "quotes", href: "/quotes", icon: FileText },
  { key: "expenses", href: "/expenses", icon: Receipt },
  { key: "creditNotes", href: "/credit-notes", icon: FileText },
  { key: "reports", href: "/reports", icon: BarChart3 },
  { key: "calendar", href: "/calendar", icon: CalendarDays },
];

const grafanaUrl =
  process.env.NEXT_PUBLIC_GRAFANA_URL || "http://localhost:3001";

const secondaryNavigation = [
  {
    key: "settings",
    href: "/settings/profile",
    icon: Settings,
    external: false,
  },
  { key: "grafana", href: grafanaUrl, icon: ExternalLink, external: true },
  { key: "help", href: "/help", icon: HelpCircle, external: false },
];

interface SidebarProps {
  collapsed?: boolean;
  onToggle?: () => void;
  mobile?: boolean;
  onNavigate?: () => void;
}

export function Sidebar({
  collapsed = false,
  onToggle,
  mobile = false,
  onNavigate,
}: SidebarProps) {
  const pathname = usePathname();
  const router = useRouter();
  const logout = useAuthStore((state) => state.logout);
  const { t } = useI18n();

  const handleLogout = () => {
    logout();
    onNavigate?.();
    router.push("/auth/login");
  };

  return (
    <aside
      className={cn(
        "brand-sidebar flex h-full flex-col border-r border-border/80 transition-all duration-300",
        mobile ? "w-72 max-w-[85vw] shadow-2xl" : collapsed ? "w-16" : "w-64"
      )}
    >
      {/* Logo */}
      <div
        className={cn(
          "flex h-16 items-center border-b",
          collapsed ? "justify-between px-2" : "justify-between px-4"
        )}
      >
        {!collapsed && (
          <Link
            href="/"
            className="flex items-center gap-2 overflow-hidden"
            onClick={onNavigate}
          >
            <Image
              src="/brand/logo.png"
              alt="Koomky"
              width={190}
              height={60}
              sizes="190px"
              className="h-9 w-auto"
              priority
            />
          </Link>
        )}
        {collapsed && (
          <Link href="/" className="flex items-center" onClick={onNavigate}>
            <Image
              src="/brand/icon.png"
              alt="Koomky"
              width={32}
              height={32}
              sizes="32px"
              className="h-8 w-8"
              priority
            />
          </Link>
        )}
        {!mobile && (
          <Button
            variant="ghost"
            size="icon"
            onClick={onToggle}
            className={cn("brand-control h-8 w-8")}
          >
            <ChevronLeft
              className={cn(
                "h-4 w-4 transition-transform",
                collapsed && "rotate-180"
              )}
            />
            <span className="sr-only">
              {collapsed ? t("sidebar.expand") : t("sidebar.collapse")}
            </span>
          </Button>
        )}
      </div>

      {/* Main Navigation */}
      <nav className="flex-1 space-y-1 p-2">
        {navigation.map((item) => {
          const isActive =
            item.href === "/"
              ? pathname === "/"
              : pathname.startsWith(item.href);

          return (
            <Link
              key={item.key}
              href={item.href}
              onClick={onNavigate}
              aria-label={t(`sidebar.${item.key}`)}
              className={cn(
                "flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-all duration-200",
                isActive
                  ? "bg-primary text-primary-foreground shadow-md shadow-primary/35"
                  : "text-muted-foreground hover:bg-accent/80 hover:text-accent-foreground",
                collapsed && "justify-center px-2"
              )}
            >
              <item.icon className="h-5 w-5 shrink-0" />
              {!collapsed && <span>{t(`sidebar.${item.key}`)}</span>}
            </Link>
          );
        })}
      </nav>

      <Separator />

      {/* Secondary Navigation */}
      <nav className="space-y-1 p-2">
        {secondaryNavigation.map((item) => {
          const isActive = item.external
            ? false
            : pathname.startsWith(item.href);

          return (
            <Link
              key={item.key}
              href={item.href}
              target={item.external ? "_blank" : undefined}
              rel={item.external ? "noopener noreferrer" : undefined}
              onClick={onNavigate}
              aria-label={t(`sidebar.${item.key}`)}
              className={cn(
                "flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-all duration-200",
                isActive
                  ? "bg-primary text-primary-foreground shadow-md shadow-primary/35"
                  : "text-muted-foreground hover:bg-accent/80 hover:text-accent-foreground",
                collapsed && "justify-center px-2"
              )}
            >
              <item.icon className="h-5 w-5 shrink-0" />
              {!collapsed && <span>{t(`sidebar.${item.key}`)}</span>}
            </Link>
          );
        })}
      </nav>

      {/* User Section */}
      <div className="border-t p-2">
        <button
          onClick={handleLogout}
          className={cn(
            "flex w-full items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium text-muted-foreground transition-all duration-200 hover:bg-accent/80 hover:text-accent-foreground",
            collapsed && "justify-center px-2"
          )}
        >
          <LogOut className="h-5 w-5 shrink-0" />
          {!collapsed && <span>{t("sidebar.logout")}</span>}
        </button>
      </div>
    </aside>
  );
}

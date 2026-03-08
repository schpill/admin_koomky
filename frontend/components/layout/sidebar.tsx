"use client";

import Image from "next/image";
import Link from "next/link";
import { usePathname } from "next/navigation";
import { useRouter } from "next/navigation";
import { useEffect, useState } from "react";
import {
  LayoutDashboard,
  Users,
  FileText,
  FolderKanban,
  Megaphone,
  GitBranch,
  GitFork,
  Ban,
  BarChart3,
  CalendarDays,
  Receipt,
  Settings,
  HelpCircle,
  ExternalLink,
  LogOut,
  ChevronLeft,
  Target,
  Calculator,
  TicketCheck,
  Brain,
  Package,
  Bell,
  LayoutTemplate,
  Gauge,
  BookOpen,
} from "lucide-react";
import { cn } from "@/lib/utils";
import { Button } from "@/components/ui/button";
import { Separator } from "@/components/ui/separator";
import { useAuthStore } from "@/lib/stores/auth";
import { useI18n } from "@/components/providers/i18n-provider";
import { apiClient } from "@/lib/api";

const navigation = [
  { key: "dashboard", href: "/", icon: LayoutDashboard },
  { key: "leads", href: "/leads", icon: Target },
  { key: "products", href: "/products", icon: Package },
  { key: "documents", href: "/documents", icon: FileText },
  { key: "tickets", href: "/tickets", icon: TicketCheck },
  { key: "clients", href: "/clients", icon: Users },
  { key: "prospects", href: "/prospects", icon: Users },
  { key: "prospectImport", href: "/prospects/import", icon: Users },
  { key: "projects", href: "/projects", icon: FolderKanban },
  { key: "campaigns", href: "/campaigns", icon: Megaphone },
  { key: "drip", href: "/campaigns/drip", icon: GitBranch },
  { key: "workflows", href: "/campaigns/workflows", icon: GitFork },
  { key: "suppression", href: "/campaigns/suppression", icon: Ban },
  { key: "invoices", href: "/invoices", icon: FileText },
  { key: "quotes", href: "/quotes", icon: FileText },
  { key: "expenses", href: "/expenses", icon: Receipt },
  { key: "creditNotes", href: "/credit-notes", icon: FileText },
  { key: "accounting", href: "/accounting", icon: Calculator },
  { key: "reports", href: "/reports", icon: BarChart3 },
  { key: "calendar", href: "/calendar", icon: CalendarDays },
  { key: "docs", href: "/docs", icon: BookOpen },
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
  {
    key: "projectTemplates",
    href: "/settings/project-templates",
    icon: LayoutTemplate,
    external: false,
  },
  {
    key: "reminders",
    href: "/settings/reminders",
    icon: Bell,
    external: false,
  },
  {
    key: "scoring",
    href: "/settings/scoring",
    icon: Gauge,
    external: false,
  },
  {
    key: "warmup",
    href: "/settings/warmup",
    icon: Gauge,
    external: false,
  },
  {
    key: "rag",
    href: "/settings/rag",
    icon: Brain,
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
  const [prospectCount, setProspectCount] = useState<number>(0);

  useEffect(() => {
    let isMounted = true;

    apiClient
      .get<any>("/clients", {
        params: { status: "prospect", per_page: 1 },
      })
      .then((response) => {
        if (!isMounted) return;
        const total = Number(response.data?.meta?.total || 0);
        setProspectCount(Number.isFinite(total) ? total : 0);
      })
      .catch(() => {
        if (!isMounted) return;
        setProspectCount(0);
      });

    return () => {
      isMounted = false;
    };
  }, []);

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
      <nav className="space-y-1 p-2">
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
              {!collapsed && (
                <span className="flex min-w-0 flex-1 items-center justify-between gap-2">
                  <span>{t(`sidebar.${item.key}`)}</span>
                  {item.key === "prospects" && prospectCount > 0 ? (
                    <span className="inline-flex min-w-5 items-center justify-center rounded-full bg-primary/15 px-1.5 py-0.5 text-xs font-semibold text-primary">
                      {prospectCount}
                    </span>
                  ) : null}
                </span>
              )}
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

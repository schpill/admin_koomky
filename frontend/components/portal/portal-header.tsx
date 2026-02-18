"use client";

import Link from "next/link";
import { usePathname } from "next/navigation";
import { LogOut } from "lucide-react";
import { Button } from "@/components/ui/button";
import { cn } from "@/lib/utils";

interface PortalHeaderProps {
  clientName?: string;
  customLogo?: string | null;
  customColor?: string | null;
  onLogout: () => void;
}

const navigation = [
  { href: "/portal/dashboard", label: "Dashboard" },
  { href: "/portal/invoices", label: "Invoices" },
  { href: "/portal/quotes", label: "Quotes" },
];

export function PortalHeader({
  clientName,
  customLogo,
  customColor,
  onLogout,
}: PortalHeaderProps) {
  const pathname = usePathname();

  return (
    <header
      className="sticky top-0 z-20 border-b bg-background/95 backdrop-blur"
      style={
        customColor
          ? ({
              borderColor: `${customColor}44`,
            } as React.CSSProperties)
          : undefined
      }
    >
      <div className="mx-auto flex w-full max-w-6xl items-center justify-between gap-3 px-4 py-3">
        <div className="flex items-center gap-4">
          <Link href="/portal/dashboard" className="flex items-center gap-3">
            {customLogo ? (
              // eslint-disable-next-line @next/next/no-img-element
              <img
                src={customLogo}
                alt="Portal brand"
                className="h-8 w-auto rounded"
              />
            ) : (
              <div
                className="rounded-md px-2 py-1 text-sm font-semibold text-white"
                style={{ backgroundColor: customColor || "#2459ff" }}
              >
                KOOMKY
              </div>
            )}
          </Link>

          <nav className="hidden items-center gap-2 md:flex">
            {navigation.map((item) => {
              const active = pathname.startsWith(item.href);
              return (
                <Link
                  key={item.href}
                  href={item.href}
                  className={cn(
                    "rounded-md px-3 py-1.5 text-sm transition",
                    active
                      ? "bg-primary text-primary-foreground"
                      : "text-muted-foreground hover:bg-muted"
                  )}
                >
                  {item.label}
                </Link>
              );
            })}
          </nav>
        </div>

        <div className="flex items-center gap-3">
          <p className="hidden text-sm text-muted-foreground sm:block">
            {clientName || "Client portal"}
          </p>
          <Button variant="outline" size="sm" onClick={onLogout}>
            <LogOut className="mr-2 h-4 w-4" />
            Logout
          </Button>
        </div>
      </div>
    </header>
  );
}

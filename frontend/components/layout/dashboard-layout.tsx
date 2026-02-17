"use client";

import { useState } from "react";
import { X } from "lucide-react";
import { usePathname } from "next/navigation";
import { Sidebar } from "./sidebar";
import { Header } from "./header";
import { BrandFooter } from "./brand-footer";
import { Button } from "@/components/ui/button";
import { PageBreadcrumbs } from "@/components/layout/page-breadcrumbs";
import { KeyboardShortcutsHelp } from "@/components/layout/keyboard-shortcuts-help";

interface DashboardLayoutProps {
  children: React.ReactNode;
}

export function DashboardLayout({ children }: DashboardLayoutProps) {
  const [sidebarCollapsed, setSidebarCollapsed] = useState(false);
  const [mobileNavOpen, setMobileNavOpen] = useState(false);
  const [shortcutsOpen, setShortcutsOpen] = useState(false);
  const pathname = usePathname();

  return (
    <div className="brand-app-shell relative flex min-h-screen overflow-hidden">
      <a
        href="#main-content"
        className="sr-only rounded-md bg-background px-3 py-2 focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-50"
      >
        Skip to content
      </a>

      <div className="hidden md:block">
        <Sidebar
          collapsed={sidebarCollapsed}
          onToggle={() => setSidebarCollapsed(!sidebarCollapsed)}
        />
      </div>

      {mobileNavOpen && (
        <div className="fixed inset-0 z-50 flex md:hidden">
          <button
            type="button"
            className="h-full flex-1 bg-black/40"
            onClick={() => setMobileNavOpen(false)}
            aria-label="Close navigation overlay"
          />
          <div className="relative h-full">
            <Sidebar mobile onNavigate={() => setMobileNavOpen(false)} />
            <Button
              type="button"
              variant="ghost"
              size="icon"
              className="brand-control absolute right-2 top-2"
              onClick={() => setMobileNavOpen(false)}
            >
              <X className="h-4 w-4" />
              <span className="sr-only">Close navigation</span>
            </Button>
          </div>
        </div>
      )}

      <div className="flex min-w-0 flex-1 flex-col overflow-hidden">
        <Header
          onOpenNavigation={() => setMobileNavOpen(true)}
          onOpenShortcuts={() => setShortcutsOpen(true)}
        />
        <PageBreadcrumbs pathname={pathname} />
        <main
          id="main-content"
          className="flex-1 overflow-auto bg-transparent p-4 md:p-6"
          tabIndex={-1}
        >
          {children}
        </main>
        <BrandFooter className="border-t border-border/70 px-6 py-3" />
      </div>

      <KeyboardShortcutsHelp
        open={shortcutsOpen}
        onOpenChange={setShortcutsOpen}
        hideTrigger
      />
      <div
        id="app-live-region"
        aria-live="polite"
        aria-atomic="true"
        className="sr-only"
      />
    </div>
  );
}

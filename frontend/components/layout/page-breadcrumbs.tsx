"use client";

import Link from "next/link";
import { usePathname } from "next/navigation";
import { ChevronRight, House } from "lucide-react";

const segmentLabels: Record<string, string> = {
  clients: "Clients",
  projects: "Projects",
  campaigns: "Campaigns",
  invoices: "Invoices",
  quotes: "Quotes",
  "credit-notes": "Credit Notes",
  reports: "Reports",
  settings: "Settings",
  profile: "Profile",
  business: "Business",
  notifications: "Notifications",
  security: "Security",
  create: "Create",
  edit: "Edit",
  analytics: "Analytics",
  compare: "Compare",
  segments: "Segments",
  email: "Email",
  sms: "SMS",
  data: "Data",
};

interface PageBreadcrumbsProps {
  pathname?: string;
}

function formatSegment(segment: string): string {
  if (segmentLabels[segment]) {
    return segmentLabels[segment];
  }

  // Keep ids and references readable without changing their meaning.
  if (/^[a-z0-9-]{6,}$/i.test(segment)) {
    return segment;
  }

  return segment
    .split("-")
    .map((part) =>
      part.length > 0 ? part.charAt(0).toUpperCase() + part.slice(1) : part
    )
    .join(" ");
}

export function PageBreadcrumbs({ pathname }: PageBreadcrumbsProps) {
  const routePathname = usePathname();
  const currentPath = pathname ?? routePathname;

  if (!currentPath || currentPath === "/") {
    return null;
  }

  const parts = currentPath.split("/").filter(Boolean);
  if (parts.length === 0) {
    return null;
  }

  return (
    <nav
      aria-label="Breadcrumb"
      className="flex items-center gap-2 px-4 py-3 text-sm text-muted-foreground md:px-6"
    >
      <Link
        href="/"
        className="inline-flex items-center rounded-md p-1 transition-colors hover:bg-accent hover:text-foreground"
      >
        <House className="h-4 w-4" />
        <span className="sr-only">Dashboard</span>
      </Link>
      {parts.map((segment, index) => {
        const href = `/${parts.slice(0, index + 1).join("/")}`;
        const label = formatSegment(decodeURIComponent(segment));
        const isLast = index === parts.length - 1;

        return (
          <span key={href} className="inline-flex items-center gap-2">
            <ChevronRight className="h-3.5 w-3.5" />
            {isLast ? (
              <span className="font-medium text-foreground">{label}</span>
            ) : (
              <Link
                href={href}
                className="rounded-md px-1 py-0.5 transition-colors hover:bg-accent hover:text-foreground"
              >
                {label}
              </Link>
            )}
          </span>
        );
      })}
    </nav>
  );
}

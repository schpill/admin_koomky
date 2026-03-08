"use client";

import Link from "next/link";
import { usePathname } from "next/navigation";
import { DOC_GROUPS, DOC_MODULES, getDocHref } from "@/lib/docs/config";
import { cn } from "@/lib/utils";

export function DocSidebar() {
  const pathname = usePathname();

  return (
    <aside className="space-y-6">
      {DOC_GROUPS.map((group) => (
        <section key={group.key} className="space-y-2">
          <p className="px-3 text-xs font-semibold uppercase tracking-[0.2em] text-muted-foreground">
            {group.label}
          </p>
          <nav className="space-y-1">
            {DOC_MODULES.filter((module) => module.category === group.key).map(
              (module) => {
                const href = getDocHref(module.slug);
                const active =
                  pathname === href || pathname.startsWith(`${href}/`);

                return (
                  <Link
                    key={module.slug}
                    href={href}
                    aria-current={active ? "page" : undefined}
                    className={cn(
                      "flex items-center gap-3 rounded-xl px-3 py-2 text-sm transition",
                      active
                        ? "bg-primary text-primary-foreground shadow-lg shadow-primary/20"
                        : "text-muted-foreground hover:bg-accent hover:text-foreground"
                    )}
                  >
                    <module.icon className="h-4 w-4 shrink-0" />
                    <span>{module.title}</span>
                  </Link>
                );
              }
            )}
          </nav>
        </section>
      ))}
    </aside>
  );
}

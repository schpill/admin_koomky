import Link from "next/link";
import { cn } from "@/lib/utils";

type DocTocProps = {
  headings: Array<{ id: string; title: string; level: 2 | 3 }>;
};

export function DocToc({ headings }: DocTocProps) {
  if (headings.length === 0) {
    return null;
  }

  return (
    <aside className="sticky top-24 hidden xl:block">
      <div className="rounded-2xl border border-border/70 bg-background/70 p-4 shadow-lg shadow-primary/5">
        <p className="mb-3 text-sm font-semibold uppercase tracking-[0.2em] text-muted-foreground">
          Sur cette page
        </p>
        <nav className="space-y-2">
          {headings.map((heading) => (
            <Link
              key={heading.id}
              href={`#${heading.id}`}
              className={cn(
                "block text-sm text-muted-foreground transition hover:text-foreground",
                heading.level === 3 && "pl-4"
              )}
            >
              {heading.title}
            </Link>
          ))}
        </nav>
      </div>
    </aside>
  );
}

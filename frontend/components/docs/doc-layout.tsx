import type { ReactNode } from "react";
import { DocSidebar } from "@/components/docs/doc-sidebar";
import { DocToc } from "@/components/docs/doc-toc";

type DocLayoutProps = {
  title: string;
  description: string;
  headings?: Array<{ id: string; title: string; level: 2 | 3 }>;
  children: ReactNode;
};

export function DocLayout({
  title,
  description,
  headings = [],
  children,
}: DocLayoutProps) {
  return (
    <div className="grid gap-8 xl:grid-cols-[260px_minmax(0,1fr)_220px]">
      <div className="xl:sticky xl:top-24 xl:self-start">
        <DocSidebar />
      </div>

      <article className="min-w-0 space-y-8">
        <header className="space-y-4 rounded-3xl border border-border/70 bg-gradient-to-br from-background via-background to-primary/10 p-8 shadow-xl shadow-primary/10">
          <p className="text-xs font-semibold uppercase tracking-[0.3em] text-primary">
            Documentation integree
          </p>
          <h1 className="text-4xl font-semibold tracking-tight">{title}</h1>
          <p className="max-w-3xl text-base leading-7 text-muted-foreground">
            {description}
          </p>
        </header>

        <div className="prose prose-slate max-w-none dark:prose-invert prose-headings:scroll-mt-24 prose-headings:font-semibold prose-a:text-primary">
          {children}
        </div>
      </article>

      <DocToc headings={headings} />
    </div>
  );
}

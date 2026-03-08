"use client";

import { useEffect, useState } from "react";

type DocDiagramProps = {
  src: string;
  title?: string;
};

export function DocDiagram({ src, title }: DocDiagramProps) {
  const [svg, setSvg] = useState("");
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    let cancelled = false;

    async function renderDiagram() {
      try {
        const response = await fetch(src);
        if (!response.ok) {
          throw new Error(`Unable to load diagram: ${src}`);
        }

        const definition = await response.text();
        const mermaid = (await import("mermaid")).default;
        mermaid.initialize({
          startOnLoad: false,
          securityLevel: "loose",
          theme: "default",
        });

        const { svg } = await mermaid.render(
          `doc-diagram-${src.replace(/[^a-z0-9]+/gi, "-")}`,
          definition
        );

        if (!cancelled) {
          setSvg(svg);
          setError(null);
        }
      } catch (err) {
        if (!cancelled) {
          setError(err instanceof Error ? err.message : "Diagram render failed");
        }
      }
    }

    renderDiagram();

    return () => {
      cancelled = true;
    };
  }, [src]);

  return (
    <figure className="my-8 space-y-3 rounded-2xl border border-border/70 bg-background/80 p-4 shadow-xl shadow-primary/5">
      {title ? <figcaption className="font-semibold">{title}</figcaption> : null}
      {error ? (
        <p className="text-sm text-destructive">{error}</p>
      ) : (
        <div
          data-testid="doc-diagram-svg"
          className="overflow-x-auto [&_svg]:mx-auto [&_svg]:h-auto [&_svg]:max-w-full"
          dangerouslySetInnerHTML={{ __html: svg }}
        />
      )}
    </figure>
  );
}

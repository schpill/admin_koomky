import { describe, expect, it } from "vitest";
import {
  extractFrontmatter,
  stripMdx,
  toSearchEntry,
} from "@/lib/docs/search-index";

describe("build-search-index helpers", () => {
  const source = `---
title: "Factures"
description: "Piloter votre facturation"
module: "invoices"
---

## Vue d'ensemble

Créez, envoyez et relancez vos factures.

<DocCallout type="tip">Utilisez les relances automatiques.</DocCallout>
`;

  it("extracts frontmatter and plain text from mdx", () => {
    const frontmatter = extractFrontmatter(source);

    expect(frontmatter.title).toBe("Factures");
    expect(frontmatter.description).toBe("Piloter votre facturation");
    expect(frontmatter.module).toBe("invoices");

    expect(stripMdx(source)).toContain("Créez, envoyez et relancez vos factures.");
    expect(stripMdx(source)).toContain("Utilisez les relances automatiques.");
  });

  it("builds a search entry from mdx content", () => {
    expect(
      toSearchEntry({
        slugSegments: ["invoices"],
        source,
      })
    ).toMatchObject({
      slug: "invoices",
      title: "Factures",
      module: "invoices",
      description: "Piloter votre facturation",
    });
  });
});

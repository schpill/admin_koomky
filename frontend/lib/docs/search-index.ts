import matter from "gray-matter";

export type DocFrontmatter = {
  title: string;
  description: string;
  module: string;
};

export type DocSearchEntry = DocFrontmatter & {
  slug: string;
  content: string;
};

export function extractFrontmatter(source: string): DocFrontmatter {
  const parsed = matter(source);

  return {
    title: String(parsed.data.title ?? ""),
    description: String(parsed.data.description ?? ""),
    module: String(parsed.data.module ?? ""),
  };
}

export function stripMdx(source: string): string {
  const parsed = matter(source);

  return parsed.content
    .replace(/<[^>]+>/g, " ")
    .replace(/!\[([^\]]*)\]\([^)]+\)/g, "$1")
    .replace(/\[([^\]]+)\]\([^)]+\)/g, "$1")
    .replace(/[#*_`>-]/g, " ")
    .replace(/\s+/g, " ")
    .trim();
}

export function toSearchEntry(input: {
  slugSegments: string[];
  source: string;
}): DocSearchEntry {
  const frontmatter = extractFrontmatter(input.source);

  return {
    ...frontmatter,
    slug: input.slugSegments.join("/"),
    content: stripMdx(input.source),
  };
}

export function getTableOfContents(source: string) {
  const headings: Array<{ id: string; title: string; level: 2 | 3 }> = [];
  const content = matter(source).content;

  for (const line of content.split("\n")) {
    const match = /^(##|###)\s+(.+)$/.exec(line.trim());
    if (!match) continue;
    const level = match[1] === "##" ? 2 : 3;
    const title = match[2].trim();
    headings.push({
      id: slugifyHeading(title),
      title,
      level,
    });
  }

  return headings;
}

export function slugifyHeading(value: string) {
  return value
    .normalize("NFD")
    .replace(/[\u0300-\u036f]/g, "")
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, "-")
    .replace(/^-+|-+$/g, "");
}

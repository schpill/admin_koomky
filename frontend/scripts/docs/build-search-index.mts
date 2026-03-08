import { promises as fs } from "fs";
import path from "path";
import matter from "gray-matter";

const docsRoot = path.join(process.cwd(), "content", "docs");
const outputFile = path.join(
  process.cwd(),
  "public",
  "docs",
  "search-index.json"
);

function toSearchEntry(input: { slugSegments: string[]; source: string }) {
  const parsed = matter(input.source);
  return {
    slug: input.slugSegments.join("/"),
    title: String(parsed.data.title ?? ""),
    description: String(parsed.data.description ?? ""),
    module: String(parsed.data.module ?? ""),
    content: parsed.content
      .replace(/<[^>]+>/g, " ")
      .replace(/!\[([^\]]*)\]\([^)]+\)/g, "$1")
      .replace(/\[([^\]]+)\]\([^)]+\)/g, "$1")
      .replace(/[#*_`>-]/g, " ")
      .replace(/\s+/g, " ")
      .trim(),
  };
}

async function walk(dir: string): Promise<string[]> {
  const entries = await fs.readdir(dir, { withFileTypes: true });
  const files = await Promise.all(
    entries.map(async (entry) => {
      const fullPath = path.join(dir, entry.name);
      if (entry.isDirectory()) {
        return walk(fullPath);
      }
      return entry.name.endsWith(".mdx") ? [fullPath] : [];
    })
  );

  return files.flat();
}

async function main() {
  const files = await walk(docsRoot);
  const entries = [];

  for (const file of files) {
    const source = await fs.readFile(file, "utf8");
    const relative = path.relative(docsRoot, file).replace(/\\/g, "/");
    const slugSegments = relative
      .replace(/\.mdx$/, "")
      .split("/")
      .filter((segment) => segment !== "index");

    entries.push(toSearchEntry({ slugSegments, source }));
  }

  await fs.mkdir(path.dirname(outputFile), { recursive: true });
  await fs.writeFile(outputFile, JSON.stringify(entries, null, 2));

  console.log(`Wrote ${entries.length} docs entries to ${outputFile}`);
}

main().catch((error) => {
  console.error(error);
  process.exitCode = 1;
});

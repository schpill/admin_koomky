import { promises as fs } from "fs";
import path from "path";
import { diagramDefinitions } from "../../content/docs/diagrams.config";

const apiKey = process.env.GEMINI_API_KEY;
const model = process.env.GEMINI_GENERATION_MODEL || "gemini-2.5-flash";

/**
 * Gemini often prefixes its answer with prose ("Sure, here is…") then
 * wraps the actual code in a markdown fence.  These helpers extract
 * only the relevant content regardless of how verbose the reply is.
 */
function extractSvg(raw: string): string | null {
  const match = raw.match(/<svg[\s\S]*?<\/svg>/i);
  return match ? match[0].trim() : null;
}

function extractMermaid(raw: string): string | null {
  // 1. Content inside a ```mermaid … ``` (or ``` … ```) fence
  const fenced = raw.match(/```(?:mermaid)?\r?\n([\s\S]*?)\r?\n```/i);
  if (fenced) return fenced[1].trim();

  // 2. Raw content that already looks like a Mermaid diagram
  const bare = raw.trim();
  if (
    /^(graph|flowchart|sequenceDiagram|stateDiagram|gantt|pie|erDiagram|journey|gitGraph)/i.test(
      bare
    )
  ) {
    return bare;
  }

  return null;
}

function extractContent(raw: string, type: "mermaid" | "svg"): string | null {
  return type === "svg" ? extractSvg(raw) : extractMermaid(raw);
}

async function generateWithGemini(
  prompt: string,
  retries = 2
): Promise<string | null> {
  if (!apiKey) {
    console.warn("  ⚠ GEMINI_API_KEY not set — using fallback content.");
    return null;
  }

  for (let attempt = 0; attempt <= retries; attempt++) {
    try {
      const response = await fetch(
        `https://generativelanguage.googleapis.com/v1beta/models/${model}:generateContent?key=${apiKey}`,
        {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({
            contents: [{ parts: [{ text: prompt }] }],
          }),
        }
      );

      if (!response.ok) {
        throw new Error(`Gemini API returned ${response.status}`);
      }

      const payload = await response.json();
      const raw: string =
        payload.candidates?.[0]?.content?.parts
          ?.map((part: { text?: string }) => part.text ?? "")
          .join("") ?? "";

      if (!raw) throw new Error("Empty response from Gemini");

      return raw; // raw returned; extraction is type-aware and done in main()
    } catch (err) {
      if (attempt < retries) {
        const delay = (attempt + 1) * 2000;
        console.warn(
          `  ↻ Attempt ${attempt + 1} failed (${(err as Error).message}), retrying in ${delay / 1000}s…`
        );
        await new Promise((resolve) => setTimeout(resolve, delay));
      } else {
        console.error(`  ✗ All attempts failed: ${(err as Error).message}`);
        return null;
      }
    }
  }
  return null;
}

function fallbackContent(id: string, type: "mermaid" | "svg") {
  if (type === "svg") {
    return `<svg xmlns="http://www.w3.org/2000/svg" width="800" height="500" viewBox="0 0 800 500"><rect width="800" height="500" fill="#f8fafc"/><rect x="32" y="32" width="736" height="436" rx="28" fill="#dbeafe"/><text x="72" y="120" font-family="Arial" font-size="32" fill="#0f172a">${id}</text><text x="72" y="172" font-family="Arial" font-size="20" fill="#334155">Fallback asset generated locally because Gemini output was unavailable.</text></svg>`;
  }

  return `flowchart TD\n    A[${id}] --> B[Analyse]\n    B --> C[Execution]\n    C --> D[Validation]\n`;
}

const FORMAT_SUFFIX: Record<"mermaid" | "svg", string> = {
  svg: "\n\nIMPORTANT: Reply ONLY with raw SVG code, starting with <svg and ending with </svg>. No prose, no markdown fences, no explanations.",
  mermaid:
    "\n\nIMPORTANT: Reply ONLY with raw Mermaid diagram code (e.g. starting with flowchart, stateDiagram-v2, sequenceDiagram, etc.). No prose, no markdown fences, no explanations.",
};

async function main() {
  const filterIds = process.argv.slice(2); // e.g. tsx script.mts invoices-overview
  const definitions =
    filterIds.length > 0
      ? diagramDefinitions.filter((d) => filterIds.includes(d.id))
      : diagramDefinitions;

  if (filterIds.length > 0 && definitions.length === 0) {
    console.error(`No definition found for: ${filterIds.join(", ")}`);
    process.exitCode = 1;
    return;
  }

  let ok = 0;
  let fallback = 0;

  for (const definition of definitions) {
    process.stdout.write(`  ${definition.id} … `);
    const outputPath = path.join(process.cwd(), definition.outputFile);
    await fs.mkdir(path.dirname(outputPath), { recursive: true });

    const prompt = definition.prompt + FORMAT_SUFFIX[definition.type];
    const raw = await generateWithGemini(prompt);
    let content: string | null = null;

    if (raw) {
      content = extractContent(raw, definition.type);
      if (!content) {
        console.warn(
          `\n  ⚠ Could not extract ${definition.type} from Gemini response — using fallback.`
        );
      }
    }

    if (!content) {
      content = fallbackContent(definition.id, definition.type);
      fallback++;
      console.log("fallback");
    } else {
      ok++;
      console.log("✓");
    }

    await fs.writeFile(outputPath, content, "utf8");
  }

  console.log(`\nDone: ${ok} generated via Gemini, ${fallback} fallback(s).`);
}

main().catch((error) => {
  console.error(error);
  process.exitCode = 1;
});

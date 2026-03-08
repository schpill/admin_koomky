import { promises as fs } from "fs";
import path from "path";
import { diagramDefinitions } from "../../content/docs/diagrams.config";

const apiKey = process.env.GEMINI_API_KEY;

async function generateWithGemini(prompt: string) {
  if (!apiKey) {
    return null;
  }

  const response = await fetch(
    `https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=${apiKey}`,
    {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        contents: [{ parts: [{ text: prompt }] }],
      }),
    }
  );

  if (!response.ok) {
    throw new Error(`Gemini API failed with ${response.status}`);
  }

  const payload = await response.json();
  return (
    payload.candidates?.[0]?.content?.parts
      ?.map((part: { text?: string }) => part.text ?? "")
      .join("") ?? null
  );
}

function fallbackContent(id: string, type: "mermaid" | "svg") {
  if (type === "svg") {
    return `<svg xmlns="http://www.w3.org/2000/svg" width="800" height="500" viewBox="0 0 800 500"><rect width="800" height="500" fill="#f8fafc"/><rect x="32" y="32" width="736" height="436" rx="28" fill="#dbeafe"/><text x="72" y="120" font-family="Arial" font-size="32" fill="#0f172a">${id}</text><text x="72" y="172" font-family="Arial" font-size="20" fill="#334155">Fallback asset generated locally because Gemini output was unavailable.</text></svg>`;
  }

  return `flowchart TD\n    A[${id}] --> B[Analyse]\n    B --> C[Execution]\n    C --> D[Validation]\n`;
}

async function main() {
  for (const definition of diagramDefinitions) {
    const outputPath = path.join(process.cwd(), definition.outputFile);
    await fs.mkdir(path.dirname(outputPath), { recursive: true });

    let content = await generateWithGemini(definition.prompt);
    if (!content) {
      content = fallbackContent(definition.id, definition.type);
    }

    await fs.writeFile(outputPath, content);
    console.log(`Generated ${definition.id} -> ${definition.outputFile}`);
  }
}

main().catch((error) => {
  console.error(error);
  process.exitCode = 1;
});

import { z } from "zod";
import { KoomkyClient } from "../koomkyClient.js";

export const askQuestionSchema = z.object({
  question: z.string().min(1).max(1000),
  client_id: z.string().uuid().optional()
});

export async function askQuestionTool(client: KoomkyClient, pat: string, input: unknown) {
  const args = askQuestionSchema.parse(input);
  const result = await client.ask(pat, args.question, args.client_id);

  return {
    content: [
      {
        type: "text" as const,
        text: `${result.answer}\n\nSources:\n${result.sources
          .map((s) => `- ${s.title ?? s.document_id}#${s.chunk_index} (${s.score.toFixed(2)})`)
          .join("\n")}`
      }
    ]
  };
}

import { z } from "zod";
import { KoomkyClient } from "../koomkyClient.js";

export const searchDocumentsSchema = z.object({
  query: z.string().min(1),
  limit: z.number().int().min(1).max(20).optional().default(5),
  client_id: z.string().uuid().optional()
});

export async function searchDocumentsTool(client: KoomkyClient, pat: string, input: unknown) {
  const args = searchDocumentsSchema.parse(input);
  const results = await client.search(pat, args.query, args.limit, args.client_id);

  return {
    content: [{ type: "text" as const, text: JSON.stringify(results, null, 2) }]
  };
}

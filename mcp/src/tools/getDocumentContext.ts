import { z } from "zod";
import { KoomkyClient } from "../koomkyClient.js";

export const getDocumentContextSchema = z.object({
  document_id: z.string().uuid()
});

export async function getDocumentContextTool(client: KoomkyClient, pat: string, input: unknown) {
  const args = getDocumentContextSchema.parse(input);
  const chunks = await client.getDocumentContext(pat, args.document_id);

  return {
    content: [{ type: "text" as const, text: JSON.stringify(chunks, null, 2) }]
  };
}

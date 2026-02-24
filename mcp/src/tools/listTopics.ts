import { z } from "zod";
import { KoomkyClient } from "../koomkyClient.js";

export const listTopicsSchema = z.object({});

export async function listTopicsTool(client: KoomkyClient, pat: string, input: unknown) {
  listTopicsSchema.parse(input);
  const topics = await client.listTopics(pat);

  return {
    content: [{ type: "text" as const, text: topics.join("\n") || "No topics available" }]
  };
}

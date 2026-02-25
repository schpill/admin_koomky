import { describe, expect, it } from "vitest";
import { messages } from "@/lib/i18n/messages";

type MessageObj = { [key: string]: string | MessageObj };

function collectLeafKeys(obj: MessageObj, prefix = ""): string[] {
  const keys: string[] = [];
  for (const [key, value] of Object.entries(obj)) {
    const fullKey = prefix ? `${prefix}.${key}` : key;
    if (typeof value === "string") {
      keys.push(fullKey);
    } else {
      keys.push(...collectLeafKeys(value as MessageObj, fullKey));
    }
  }
  return keys;
}

describe("i18n completeness", () => {
  const enKeys = new Set(collectLeafKeys(messages.en as MessageObj));
  const frKeys = new Set(collectLeafKeys(messages.fr as MessageObj));

  it("FR contains every key present in EN", () => {
    const missing = [...enKeys].filter((k) => !frKeys.has(k));
    expect(
      missing,
      `Keys in EN but missing in FR:\n${missing.map((k) => `  - ${k}`).join("\n")}`
    ).toHaveLength(0);
  });

  it("EN contains every key present in FR", () => {
    const missing = [...frKeys].filter((k) => !enKeys.has(k));
    expect(
      missing,
      `Keys in FR but missing in EN:\n${missing.map((k) => `  - ${k}`).join("\n")}`
    ).toHaveLength(0);
  });
});

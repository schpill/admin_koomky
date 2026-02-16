import { describe, expect, test } from "vitest";
import { resolveLocaleFromAcceptLanguage } from "@/lib/i18n/config";

describe("resolveLocaleFromAcceptLanguage", () => {
  test("returns default locale when header is missing", () => {
    expect(resolveLocaleFromAcceptLanguage(undefined)).toBe("fr");
  });

  test("detects english locale from regioned tag", () => {
    expect(resolveLocaleFromAcceptLanguage("en-US,en;q=0.9")).toBe("en");
  });

  test("detects french locale from regioned tag", () => {
    expect(resolveLocaleFromAcceptLanguage("fr-CA,fr;q=0.9,en;q=0.8")).toBe(
      "fr"
    );
  });

  test("honors q priority", () => {
    expect(resolveLocaleFromAcceptLanguage("fr;q=0.5,en;q=0.9")).toBe("en");
  });

  test("falls back to default for unsupported languages", () => {
    expect(resolveLocaleFromAcceptLanguage("de-DE,de;q=0.9")).toBe("fr");
  });
});

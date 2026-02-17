import { expect, test, type Page } from "@playwright/test";
import axe from "axe-core";
import { mockProtectedApi, seedAuthenticatedSession } from "../helpers/session";

type AxeImpact = "minor" | "moderate" | "serious" | "critical" | null;

interface AxeNode {
  target: string[];
}

interface AxeViolation {
  id: string;
  impact: AxeImpact;
  nodes: AxeNode[];
}

interface AxeResults {
  violations: AxeViolation[];
}

async function runAxeAudit(page: Page): Promise<AxeResults> {
  await page.addScriptTag({ content: axe.source });

  return page.evaluate(async () => {
    const axe = (window as any).axe;
    if (!axe) {
      throw new Error("axe-core script was not loaded");
    }

    return axe.run(document, {
      runOnly: {
        type: "tag",
        values: ["wcag2a", "wcag2aa"],
      },
    });
  });
}

function formatViolations(violations: AxeViolation[]): string {
  return violations
    .map((violation) => {
      const targets = violation.nodes
        .map((node) => node.target.join(" "))
        .join(" | ");
      return `${violation.id} (${violation.impact}) -> ${targets}`;
    })
    .join("\n");
}

async function assertNoCriticalViolations(
  page: Page,
  route: string
): Promise<void> {
  await page.goto(route, { waitUntil: "domcontentloaded" });

  const results = await runAxeAudit(page);
  const criticalViolations = results.violations.filter(
    (violation) => violation.impact === "critical"
  );

  expect(
    criticalViolations,
    `Critical accessibility violations on ${route}:\n${formatViolations(
      criticalViolations
    )}`
  ).toHaveLength(0);
}

test.describe("Accessibility axe-core audit", () => {
  test("login page has no critical violations", async ({ page }) => {
    await assertNoCriticalViolations(page, "/auth/login");
  });

  test("dashboard page has no critical violations", async ({ page }) => {
    await seedAuthenticatedSession(page);
    await mockProtectedApi(page);
    await assertNoCriticalViolations(page, "/");
  });

  test("clients page has no critical violations", async ({ page }) => {
    await seedAuthenticatedSession(page);
    await mockProtectedApi(page);
    await assertNoCriticalViolations(page, "/clients");
  });

  test("invoices page has no critical violations", async ({ page }) => {
    await seedAuthenticatedSession(page);
    await mockProtectedApi(page);
    await assertNoCriticalViolations(page, "/invoices");
  });

  test("campaigns page has no critical violations", async ({ page }) => {
    await seedAuthenticatedSession(page);
    await mockProtectedApi(page);
    await assertNoCriticalViolations(page, "/campaigns");
  });
});

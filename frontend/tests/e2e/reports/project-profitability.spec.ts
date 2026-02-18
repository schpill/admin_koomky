import { expect, test } from "@playwright/test";
import { mockProtectedApi, seedAuthenticatedSession } from "../helpers/session";

test.describe("Project profitability report", () => {
  test("project profitability page is reachable", async ({ page }) => {
    await seedAuthenticatedSession(page);
    await mockProtectedApi(page);

    await page.goto("/reports/project-profitability");

    await expect(
      page.getByRole("heading", {
        name: "Project profitability",
        exact: true,
      })
    ).toBeVisible();
  });
});

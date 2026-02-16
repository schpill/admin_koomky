import { test, expect } from "@playwright/test";

test.describe("Projects CRUD", () => {
  test("placeholder flow for project creation", async ({ page }) => {
    await page.goto("/projects");

    await expect(page.getByRole("heading", { name: "Projects" })).toBeVisible();
  });
});

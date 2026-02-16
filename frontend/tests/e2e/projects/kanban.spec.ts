import { test, expect } from "@playwright/test";

test.describe("Project Kanban", () => {
  test("placeholder kanban board visibility", async ({ page }) => {
    await page.goto("/projects");

    await expect(page.getByText("To do")).toBeVisible();
  });
});

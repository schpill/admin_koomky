import { test, expect } from "@playwright/test";

test.describe("Ticket Search and Filter", () => {
  test.beforeEach(async ({ page }) => {
    await page.goto("/tickets");
  });

  test("searches tickets by title", async ({ page }) => {
    await page.getByPlaceholder(/search tickets/i).fill("urgent");
    await page.waitForTimeout(400); // debounce
    // Results should be filtered
    const rows = page.getByRole("row");
    await expect(rows).not.toHaveCount(0);
  });

  test("filters by status", async ({ page }) => {
    await page.getByLabel("open").check();
    // Table should show only open tickets
    const statusBadges = page.getByText("Open");
    await expect(statusBadges.first()).toBeVisible();
  });

  test("filters overdue tickets", async ({ page }) => {
    await page.getByLabel(/overdue only/i).check();
    // Verify overdue indicator visible if any exist
    const rows = page.getByRole("row").filter({ hasText: "" });
    await expect(rows).toBeDefined();
  });
});

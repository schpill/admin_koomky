import { test, expect } from "@playwright/test";

test.describe("Ticket Status Workflow", () => {
  test("changes status from open to in_progress", async ({ page }) => {
    await page.goto("/tickets");
    // Click first ticket
    await page.getByRole("row").nth(1).click();
    await page.getByRole("button", { name: /change status/i }).click();
    // Select in_progress
    await page.getByRole("combobox").selectOption("in_progress");
    await page
      .getByRole("button", { name: /change status/i, exact: true })
      .click();
    await expect(page.getByText("In Progress")).toBeVisible();
  });

  test("reopens a resolved ticket", async ({ page }) => {
    // Navigate to a resolved ticket
    await page.goto("/tickets?status=resolved");
    await page.getByRole("row").nth(1).click();
    await page.getByRole("button", { name: /change status/i }).click();
    await page.getByRole("option", { name: /open/i }).click();
    await page
      .getByRole("button", { name: /change status/i, exact: true })
      .click();
    await expect(page.getByText("Open")).toBeVisible();
  });
});

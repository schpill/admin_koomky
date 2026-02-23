import { test, expect } from "@playwright/test";

test.describe("Ticket Creation", () => {
  test.beforeEach(async ({ page }) => {
    // Assume already authenticated
    await page.goto("/tickets");
  });

  test("creates ticket without client — shows Divers", async ({ page }) => {
    await page.getByRole("button", { name: /new ticket/i }).click();
    await page.getByLabel(/title/i).fill("Test ticket");
    await page.getByLabel(/description/i).fill("Test description");
    // No client selected — project shows Divers placeholder
    await expect(page.getByPlaceholder("Divers")).toBeVisible();
    // Submit
    await page.getByRole("button", { name: /create ticket/i }).click();
    await expect(page).toHaveURL(/\/tickets\/[a-z0-9-]+/);
  });

  test("creates ticket with urgent priority", async ({ page }) => {
    await page.getByRole("button", { name: /new ticket/i }).click();
    await page.getByLabel(/title/i).fill("Urgent issue");
    await page.getByLabel(/description/i).fill("Critical problem");
    // Select urgent priority
    await page.getByRole("combobox", { name: /priority/i }).click();
    await page.getByRole("option", { name: /urgent/i }).click();
    await page.getByRole("button", { name: /create ticket/i }).click();
    await expect(page.getByText("Urgent")).toBeVisible();
  });

  test("ticket appears in list after creation", async ({ page }) => {
    await page.getByRole("button", { name: /new ticket/i }).click();
    const title = `E2E ticket ${Date.now()}`;
    await page.getByLabel(/title/i).fill(title);
    await page.getByLabel(/description/i).fill("Created via E2E test");
    await page.getByRole("button", { name: /create ticket/i }).click();
    await page.goto("/tickets");
    await expect(page.getByText(title)).toBeVisible();
  });
});

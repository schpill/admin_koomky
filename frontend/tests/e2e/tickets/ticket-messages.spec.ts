import { test, expect } from "@playwright/test";

test.describe("Ticket Messages", () => {
  test("adds a public message", async ({ page }) => {
    await page.goto("/tickets");
    await page.getByRole("row").nth(1).click();
    const msg = `Public message ${Date.now()}`;
    await page.getByPlaceholder(/write a message/i).fill(msg);
    await page.getByRole("button", { name: /send/i }).click();
    await expect(page.getByText(msg)).toBeVisible();
  });

  test("adds an internal note (visible to owner)", async ({ page }) => {
    await page.goto("/tickets");
    await page.getByRole("row").nth(1).click();
    await page
      .getByPlaceholder(/write a message/i)
      .fill("Internal note content");
    await page.getByLabel(/internal note/i).check();
    await page.getByRole("button", { name: /send/i }).click();
    await expect(page.getByText("Internal note")).toBeVisible();
  });
});

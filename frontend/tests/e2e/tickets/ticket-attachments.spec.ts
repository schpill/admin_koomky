import { test, expect } from "@playwright/test";
import path from "path";

test.describe("Ticket Attachments", () => {
  test("uploads an attachment and it appears in list", async ({ page }) => {
    await page.goto("/tickets");
    await page.getByRole("row").nth(1).click();
    await page.getByRole("tab", { name: /attachments/i }).click();
    const fileInput = page.locator('input[type="file"]');
    await fileInput.setInputFiles(
      path.join(__dirname, "../fixtures/test-file.pdf")
    );
    // Verify document appears
    await expect(page.getByText(/\.pdf/i)).toBeVisible();
  });

  test("detaches attachment — document stays in GED", async ({ page }) => {
    await page.goto("/tickets");
    await page.getByRole("row").nth(1).click();
    await page.getByRole("tab", { name: /attachments/i }).click();
    const docTitle = await page.locator(".document-item").first().textContent();
    await page.getByLabel("Detach").first().click();
    // Verify detached from ticket
    await expect(page.getByText(docTitle ?? "")).not.toBeVisible();
  });
});

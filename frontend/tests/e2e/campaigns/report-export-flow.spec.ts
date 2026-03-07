import { expect, test } from "@playwright/test";
import { mockProtectedApi, seedAuthenticatedSession } from "../helpers/session";

test.describe("Campaign report export flow", () => {
  test("shows report export actions and requests csv/pdf exports", async ({
    page,
  }) => {
    await seedAuthenticatedSession(page);
    await mockProtectedApi(page);

    let csvRequested = false;
    let pdfRequested = false;

    await page.route("**/api/v1/campaigns/*/report/csv", async (route) => {
      csvRequested = true;
      await route.fulfill({
        status: 200,
        contentType: "text/csv",
        body: "date,email,status\n2026-03-06,test@example.com,sent\n",
      });
    });

    await page.route("**/api/v1/campaigns/*/report/pdf", async (route) => {
      pdfRequested = true;
      await route.fulfill({
        status: 200,
        contentType: "application/pdf",
        body: "%PDF-1.4 test",
      });
    });

    await page.goto("/campaigns/camp_1/analytics");

    await expect(
      page.getByRole("button", { name: /export csv/i })
    ).toBeVisible();
    await expect(
      page.getByRole("button", { name: /export pdf/i })
    ).toBeVisible();

    await page.getByRole("button", { name: /export csv/i }).click();
    await page.getByRole("button", { name: /export pdf/i }).click();

    await expect.poll(() => csvRequested).toBe(true);
    await expect.poll(() => pdfRequested).toBe(true);
  });
});

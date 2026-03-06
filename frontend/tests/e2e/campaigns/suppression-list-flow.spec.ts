import { expect, test } from "@playwright/test";
import { seedAuthenticatedSession } from "../helpers/session";

test("suppression list page is reachable", async ({ page }) => {
  await seedAuthenticatedSession(page);

  await page.route("**/api/v1/suppression-list**", async (route) => {
    await route.fulfill({
      status: 200,
      contentType: "application/json",
      body: JSON.stringify({
        status: "Success",
        data: {
          data: [],
          total: 0,
          current_page: 1,
        },
      }),
    });
  });

  await page.goto("/campaigns/suppression");
  await expect(
    page.getByRole("heading", { name: "Suppression List" })
  ).toBeVisible();
});

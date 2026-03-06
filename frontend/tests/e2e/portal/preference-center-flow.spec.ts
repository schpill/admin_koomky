import { expect, test } from "@playwright/test";
import { seedPortalSession } from "../helpers/session";

test.describe("Portal preference center", () => {
  test("loads and submits communication preferences", async ({ page }) => {
    let postedPayload: unknown = null;

    await seedPortalSession(page);
    await page.addInitScript(() => {
      window.localStorage.setItem(
        "koomky-portal-preferences-contact_1",
        JSON.stringify({
          contact_id: "contact_1",
          preferences: [
            { category: "newsletter", subscribed: true },
            { category: "promotional", subscribed: false },
            { category: "transactional", subscribed: true },
          ],
        })
      );
    });

    await page.route("**/portal/preferences/**", async (route) => {
      if (route.request().resourceType() === "document") {
        await route.continue();
        return;
      }

      if (route.request().method() === "POST") {
        postedPayload = route.request().postDataJSON();
      }

      await route.fulfill({
        status: 200,
        contentType: "application/json",
        body: JSON.stringify({
          status: "Success",
          message: "OK",
          data: {
            contact_id: "contact_1",
            preferences: [
              { category: "newsletter", subscribed: true },
              { category: "promotional", subscribed: false },
              { category: "transactional", subscribed: true },
            ],
          },
        }),
      });
    });

    await page.goto("/portal/preferences/contact_1?signature=test");

    await expect(
      page.getByRole("heading", { name: /communication preferences/i })
    ).toBeVisible();

    await page.getByRole("button", { name: /save preferences/i }).click();

    await expect.poll(() => postedPayload).not.toBeNull();
  });
});

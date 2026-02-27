import { expect, test } from "@playwright/test";
import { seedAuthenticatedSession } from "../helpers/session";

test.describe("Product campaign generation", () => {
  test("campaign wizard page is reachable", async ({ page }) => {
    await seedAuthenticatedSession(page);

    await page.route("**/api/v1/segments?**", async (route) => {
      await route.fulfill({
        status: 200,
        contentType: "application/json",
        body: JSON.stringify({
          status: "Success",
          data: [{ id: "seg_1", name: "Leads Qualifiés", contacts_count: 2 }],
        }),
      });
    });

    await page.goto("/products/prod_1/campaigns/generate");

    await expect(
      page.getByRole("heading", { name: "Générer une campagne email IA" })
    ).toBeVisible();
    await expect(
      page.getByText("Étape 1 — Choisir votre audience")
    ).toBeVisible();
  });
});

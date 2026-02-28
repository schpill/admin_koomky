import { expect, test } from "@playwright/test";
import { mockProtectedApi, seedAuthenticatedSession } from "../helpers/session";

test.describe("Project template CRUD", () => {
  test("creates a project template from the dedicated form", async ({ page }) => {
    await seedAuthenticatedSession(page);
    await mockProtectedApi(page);

    await page.route("**/api/v1/project-templates", async (route) => {
      if (route.request().method() === "POST") {
        await route.fulfill({
          status: 201,
          contentType: "application/json",
          body: JSON.stringify({
            status: "Success",
            message: "Created",
            data: {
              id: "template-1",
              name: "Website template",
              description: null,
              billing_type: "hourly",
              default_hourly_rate: 120,
              default_currency: "EUR",
              estimated_hours: 16,
              tasks_count: 0,
              created_at: new Date().toISOString(),
              tasks: [],
            },
          }),
        });

        return;
      }

      await route.fulfill({
        status: 200,
        contentType: "application/json",
        body: JSON.stringify({
          status: "Success",
          message: "OK",
          data: {
            data: [
              {
                id: "template-1",
                name: "Website template",
                description: null,
                billing_type: "hourly",
                default_hourly_rate: 120,
                default_currency: "EUR",
                estimated_hours: 16,
                tasks_count: 0,
                created_at: new Date().toISOString(),
                tasks: [],
              },
            ],
            current_page: 1,
            last_page: 1,
            per_page: 15,
            total: 1,
          },
        }),
      });
    });

    await page.goto("/settings/project-templates/new");

    await page.getByLabel("Nom du template").fill("Website template");
    await page.getByRole("button", { name: "Créer le template" }).click();

    await expect(page).toHaveURL(/\/settings\/project-templates\/template-1$/);
    await expect(
      page.getByRole("heading", { name: "Website template" })
    ).toBeVisible();
  });
});

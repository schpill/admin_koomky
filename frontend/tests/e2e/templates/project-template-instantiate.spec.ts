import { expect, test } from "@playwright/test";
import { mockProtectedApi, seedAuthenticatedSession } from "../helpers/session";

test.describe("Project template instantiation", () => {
  test("instantiates a project from a template card", async ({ page }) => {
    await seedAuthenticatedSession(page);
    await mockProtectedApi(page);

    await page.route("**/api/v1/project-templates", async (route) => {
      await route.fulfill({
        status: 200,
        contentType: "application/json",
        body: JSON.stringify({
          status: "Success",
          message: "OK",
          data: [
            {
              id: "template-1",
              name: "Website template",
              description: "Reusable delivery flow",
              billing_type: "hourly",
              default_hourly_rate: 120,
              default_currency: "EUR",
              estimated_hours: 16,
              tasks_count: 1,
              created_at: new Date().toISOString(),
              tasks: [
                {
                  id: "task-1",
                  title: "Kickoff",
                  description: null,
                  estimated_hours: null,
                  priority: "medium",
                  sort_order: 0,
                },
              ],
            },
          ],
        }),
      });
    });

    await page.route("**/api/v1/clients**", async (route) => {
      await route.fulfill({
        status: 200,
        contentType: "application/json",
        body: JSON.stringify({
          status: "Success",
          message: "OK",
          data: {
            data: [
              {
                id: "client-1",
                name: "Acme",
              },
            ],
            meta: {
              current_page: 1,
              last_page: 1,
              total: 1,
              per_page: 15,
            },
          },
        }),
      });
    });

    await page.route("**/api/v1/project-templates/template-1/instantiate", async (route) => {
      await route.fulfill({
        status: 201,
        contentType: "application/json",
        body: JSON.stringify({
          status: "Success",
          message: "Created",
          data: {
            id: "project-1",
            name: "Client rollout",
            client_id: "client-1",
            tasks_count: 1,
          },
        }),
      });
    });

    await page.goto("/settings/project-templates");

    await page.getByRole("button", { name: "Utiliser" }).click();
    await page.getByLabel("Nom du projet").fill("Client rollout");
    await page.getByLabel("Client", { exact: true }).selectOption("client-1");
    await page.getByRole("button", { name: "Créer le projet" }).click();

    await expect(page).toHaveURL(/\/projects\/project-1$/);
  });
});

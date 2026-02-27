import { expect, test } from "@playwright/test";
import { mockProtectedApi, seedAuthenticatedSession } from "../helpers/session";

test("reminder sequence settings page renders and loads sequences", async ({
  page,
}) => {
  await seedAuthenticatedSession(page);
  await mockProtectedApi(page);

  await page.route("**/api/v1/reminder-sequences", (route) =>
    route.fulfill({
      status: 200,
      contentType: "application/json",
      body: JSON.stringify({
        status: "Success",
        message: "OK",
        data: [
          {
            id: "seq_1",
            user_id: "user_1",
            name: "Relance standard",
            description: "3 étapes",
            is_active: true,
            is_default: true,
            steps: [
              {
                step_number: 1,
                delay_days: 3,
                subject: "Rappel",
                body: "Bonjour {{client_name}}",
              },
            ],
          },
        ],
      }),
    })
  );

  await page.goto("/settings/reminders");

  await expect(
    page.getByRole("heading", { name: "Relances automatiques" })
  ).toBeVisible();
  await expect(page.getByText("Relance standard")).toBeVisible();
  await expect(
    page.getByRole("link", { name: "+ Nouvelle séquence" })
  ).toBeVisible();
});

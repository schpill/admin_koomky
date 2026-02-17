import { test, expect } from "@playwright/test";
import { mockProtectedApi, seedAuthenticatedSession } from "../helpers/session";

test.describe("Calendar sync settings", () => {
  test("manages connections and persists auto-event rules", async ({
    page,
  }) => {
    await seedAuthenticatedSession(page);
    await mockProtectedApi(page);

    await page.goto("/settings/calendar");

    await expect(
      page.getByRole("heading", { name: "Calendar settings", exact: true })
    ).toBeVisible();
    await expect(page.getByLabel("Connection name")).toBeVisible();

    await page.getByLabel("Connection name").fill("Team Calendar");
    await page.getByRole("button", { name: "Save connection" }).click();
    await expect(page.getByText("Team Calendar")).toBeVisible();

    const teamConnectionCard = page
      .locator("div.rounded-md")
      .filter({ hasText: "Team Calendar" })
      .first();
    await teamConnectionCard
      .getByRole("button", { name: "Disable", exact: true })
      .click();
    await expect(teamConnectionCard).toContainText("disabled");

    const taskDueDates = page.getByLabel("Task due dates");
    await taskDueDates.uncheck();
    await page
      .getByRole("button", { name: "Save auto-event rules", exact: true })
      .click();

    await page.reload();
    await expect(page.getByLabel("Task due dates")).not.toBeChecked();
    await expect(page.getByLabel("Project deadlines")).toBeChecked();
  });
});

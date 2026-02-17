import { test, expect } from "@playwright/test";
import { mockProtectedApi, seedAuthenticatedSession } from "../helpers/session";

test.describe("Calendar sync settings", () => {
  test("calendar settings page exposes connection management", async ({
    page,
  }) => {
    await seedAuthenticatedSession(page);
    await mockProtectedApi(page);

    await page.goto("/settings/calendar");

    await expect(
      page.getByRole("heading", { name: "Calendar settings", exact: true })
    ).toBeVisible();
    await expect(page.getByLabel("Connection name")).toBeVisible();
    await expect(page.getByText("Google Work")).toBeVisible();
  });
});

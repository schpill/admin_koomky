import { test, expect } from "@playwright/test";
import { mockProtectedApi, seedAuthenticatedSession } from "../helpers/session";

test.describe("Calendar views", () => {
  test("calendar page supports month/week/day navigation and create event", async ({
    page,
  }) => {
    await seedAuthenticatedSession(page);
    await mockProtectedApi(page);

    await page.goto("/calendar");

    await expect(
      page.getByRole("heading", { name: "Calendar", exact: true })
    ).toBeVisible();

    await page.getByRole("button", { name: "Week" }).click();
    await page.getByRole("button", { name: "Day" }).click();
    await page.getByRole("button", { name: "Month" }).click();

    await page.getByRole("button", { name: "Create event" }).click();
    await expect(page.getByLabel("Title")).toBeVisible();
  });
});

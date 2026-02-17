import { test, expect } from "@playwright/test";
import { mockProtectedApi, seedAuthenticatedSession } from "../helpers/session";

test.describe("Calendar views", () => {
  test("supports view switch and full event create-update-delete flow", async ({
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

    await page.getByLabel("Title").fill("Quarterly Review");
    await page.getByLabel("Location").fill("HQ Room 4");
    await page.getByRole("button", { name: "Save event", exact: true }).click();

    const createdEventButton = page.getByRole("button", {
      name: /Quarterly Review/,
    });
    await expect(createdEventButton).toBeVisible();

    await createdEventButton.click();
    await page.getByRole("button", { name: "Edit", exact: true }).click();
    await expect(
      page.getByRole("heading", { name: "Edit event", exact: true })
    ).toBeVisible();

    await page.getByLabel("Title").fill("Quarterly Review Updated");
    await page
      .getByRole("button", { name: "Update event", exact: true })
      .click();

    const updatedEventButton = page.getByRole("button", {
      name: /Quarterly Review Updated/,
    });
    await expect(updatedEventButton).toBeVisible();

    await updatedEventButton.click();
    await page.getByRole("button", { name: "Delete", exact: true }).click();
    await expect(updatedEventButton).toHaveCount(0);
  });
});

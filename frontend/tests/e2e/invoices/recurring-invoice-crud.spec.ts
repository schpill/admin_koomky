import { test, expect } from "@playwright/test";
import { mockProtectedApi, seedAuthenticatedSession } from "../helpers/session";

test.describe("Recurring invoices", () => {
  test("can create a recurring profile from the form", async ({ page }) => {
    await seedAuthenticatedSession(page);
    await mockProtectedApi(page);

    await page.goto("/invoices/recurring/create");

    await expect(
      page.getByRole("heading", {
        name: "Create recurring profile",
        exact: true,
      })
    ).toBeVisible();

    await page.getByLabel("Client", { exact: true }).selectOption({
      label: "Acme",
    });
    await page.getByLabel("Profile name").fill("Automation Retainer");
    await page
      .locator("#line-description-0")
      .fill("Monthly support and maintenance");
    await page.locator("#line-unit-price-0").fill("1200");

    await page.getByRole("button", { name: "Create profile" }).click();

    await expect(page).toHaveURL(/\/invoices\/recurring\/rip_1$/);
    await expect(
      page.getByRole("heading", { name: "Monthly retainer", exact: true })
    ).toBeVisible();
    await expect(page.getByText("Generated invoices")).toBeVisible();
  });

  test("can pause resume and cancel a profile from the list", async ({
    page,
  }) => {
    await seedAuthenticatedSession(page);
    await mockProtectedApi(page);

    await page.goto("/invoices/recurring");

    const row = page.getByRole("row", { name: /Monthly retainer/i });
    await expect(row).toContainText("active");

    await row.getByRole("button", { name: "Pause", exact: true }).click();
    await expect(row).toContainText("paused");
    await expect(
      row.getByRole("button", { name: "Resume", exact: true })
    ).toBeVisible();

    await row.getByRole("button", { name: "Resume", exact: true }).click();
    await expect(row).toContainText("active");

    await row.getByRole("button", { name: "Cancel", exact: true }).click();
    await expect(row).toContainText("cancelled");
    await expect(
      row.getByRole("button", { name: "Cancel", exact: true })
    ).toHaveCount(0);
  });
});

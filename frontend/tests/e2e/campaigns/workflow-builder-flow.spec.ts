import { expect, test } from "@playwright/test";
import { seedAuthenticatedSession } from "../helpers/session";

test("workflow index is reachable", async ({ page }) => {
  await seedAuthenticatedSession(page);

  await page.route("**/api/v1/workflows**", async (route) => {
    await route.fulfill({
      status: 200,
      contentType: "application/json",
      body: JSON.stringify({
        status: "Success",
        data: [],
      }),
    });
  });

  await page.goto("/campaigns/workflows");
  await expect(
    page.getByRole("heading", { name: "Workflow automations" })
  ).toBeVisible();
});

test("workflow create page is reachable", async ({ page }) => {
  await seedAuthenticatedSession(page);

  await page.goto("/campaigns/workflows/create");
  await expect(
    page.getByRole("heading", { name: "Create workflow" })
  ).toBeVisible();
  await expect(page.getByTestId("workflow-builder-canvas")).toBeVisible();
  await expect(page.getByText("Navigator")).toBeVisible();
});

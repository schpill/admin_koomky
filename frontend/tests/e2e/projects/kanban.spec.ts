import { test, expect } from "@playwright/test";
import { mockProtectedApi, seedAuthenticatedSession } from "../helpers/session";

test.describe("Project Kanban", () => {
  test("placeholder kanban board visibility", async ({ page }) => {
    await seedAuthenticatedSession(page);
    await mockProtectedApi(page);

    await page.goto("/projects");

    await expect(
      page.getByRole("heading", { name: "Projects", exact: true })
    ).toBeVisible();
  });
});

import { test, expect } from "@playwright/test";
import {
  mockProtectedApi,
  seedAuthenticatedSession,
} from "../helpers/session";

test.describe("Projects CRUD", () => {
  test("placeholder flow for project creation", async ({ page }) => {
    await seedAuthenticatedSession(page);
    await mockProtectedApi(page);

    await page.goto("/projects");

    await expect(
      page.getByRole("heading", { name: "Projects", exact: true })
    ).toBeVisible();
  });
});

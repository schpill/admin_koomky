import { expect, test } from "@playwright/test";
import { mockProtectedApi, seedAuthenticatedSession } from "../helpers/session";

test.describe("Project expenses", () => {
  test("project detail shows expenses tab", async ({ page }) => {
    await seedAuthenticatedSession(page);
    await mockProtectedApi(page);

    await page.goto("/projects/prj_1");

    await expect(
      page.getByRole("heading", { name: "Website redesign" })
    ).toBeVisible();

    await page.getByRole("tab", { name: "Expenses" }).click();

    await expect(page.getByText("Allocated expense")).toBeVisible();
  });
});

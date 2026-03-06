import { expect, test } from "@playwright/test";
import { mockProtectedApi, seedAuthenticatedSession } from "../helpers/session";

test.describe("Campaign click tracking flow", () => {
  test("loads campaign link analytics table with tracked URLs", async ({
    page,
  }) => {
    await seedAuthenticatedSession(page);
    await mockProtectedApi(page);

    await page.goto("/campaigns/camp_1/analytics");

    await expect(
      page.getByRole("heading", { name: /analytics/i })
    ).toBeVisible();
    await expect(page.getByText("Mock campaign")).toBeVisible();
    await expect(page.getByText("Link analytics")).toBeVisible();
    await expect(page.getByText("https://example.com/a")).toBeVisible();
    await expect(page.getByText("50%")).toBeVisible();
    await expect(
      page.getByRole("button", { name: "Export", exact: true })
    ).toBeVisible();
  });
});

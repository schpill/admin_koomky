import { expect, test } from "@playwright/test";
import { mockProtectedApi, seedAuthenticatedSession } from "../helpers/session";

test.describe("Responsive navigation", () => {
  test("hamburger menu works on mobile", async ({ page }) => {
    await page.setViewportSize({ width: 375, height: 812 });
    await seedAuthenticatedSession(page);
    await mockProtectedApi(page);

    await page.goto("/");

    const openNavigationButton = page.getByRole("button", {
      name: /Ouvrir la navigation|Open navigation/i,
    });
    await expect(openNavigationButton).toBeVisible();

    await openNavigationButton.click();

    const overlayCloseButton = page.getByRole("button", {
      name: "Close navigation overlay",
    });
    await expect(overlayCloseButton).toBeVisible();
    await expect(page.locator("aside.brand-sidebar").last()).toBeVisible();

    await overlayCloseButton.click();
    await expect(overlayCloseButton).toBeHidden();
  });

  test("sidebar can collapse on tablet", async ({ page }) => {
    await page.setViewportSize({ width: 1024, height: 768 });
    await seedAuthenticatedSession(page);
    await mockProtectedApi(page);

    await page.goto("/");

    const sidebar = page.locator("aside.brand-sidebar").first();
    await expect(sidebar).toBeVisible();
    await expect(sidebar).toHaveClass(/w-64/);

    const collapseButton = page.getByRole("button", {
      name: /Reduire la navigation|Collapse navigation/i,
    });
    await collapseButton.click();

    await expect(sidebar).toHaveClass(/w-16/);
    await expect(
      page.getByRole("button", {
        name: /Etendre la navigation|Expand navigation/i,
      })
    ).toBeVisible();
  });
});

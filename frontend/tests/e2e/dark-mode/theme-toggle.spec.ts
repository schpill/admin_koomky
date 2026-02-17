import { expect, test } from "@playwright/test";
import { mockProtectedApi, seedAuthenticatedSession } from "../helpers/session";

test.describe("Dark mode theme toggle", () => {
  test("toggles dark mode and persists after reload", async ({ page }) => {
    await seedAuthenticatedSession(page);
    await mockProtectedApi(page);
    await page.goto("/");

    const toggleThemeButton = page.getByRole("button", {
      name: /Basculer le theme|Toggle theme/i,
    });
    await expect(toggleThemeButton).toBeVisible();

    const initialIsDark = await page.evaluate(() =>
      document.documentElement.classList.contains("dark")
    );

    const initialBackgroundColor = await page.evaluate(() =>
      getComputedStyle(document.body).backgroundColor
    );

    await toggleThemeButton.click();

    const expectedIsDark = !initialIsDark;
    await expect
      .poll(async () => {
        return page.evaluate(() =>
          document.documentElement.classList.contains("dark")
        );
      })
      .toBe(expectedIsDark);

    const toggledBackgroundColor = await page.evaluate(() =>
      getComputedStyle(document.body).backgroundColor
    );
    expect(toggledBackgroundColor).not.toBe(initialBackgroundColor);

    await page.reload();

    await expect
      .poll(async () => {
        return page.evaluate(() =>
          document.documentElement.classList.contains("dark")
        );
      })
      .toBe(expectedIsDark);

    await expect
      .poll(async () => {
        return page.evaluate(() => window.localStorage.getItem("theme"));
      })
      .toBe(expectedIsDark ? "dark" : "light");
  });
});

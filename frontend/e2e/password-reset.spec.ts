import { test, expect } from "@playwright/test";

test.describe("Password Reset Flow", () => {
  test("user can request a reset link", async ({ page }) => {
    // Mock the forgot-password API
    await page.route("**/api/v1/auth/forgot-password", async (route) => {
      await route.fulfill({
        status: 200,
        contentType: "application/json",
        body: JSON.stringify({
          status: "Success",
          message: "We have emailed your password reset link.",
        }),
      });
    });

    await page.goto("/auth/forgot-password");
    await page.fill('input[id="email"]', "test@example.com");
    await page.click('button[type="submit"]');

    // Should show success message (toast)
    await expect(page.getByText("We have emailed your password reset link.")).toBeVisible();
  });

  test("user can reset password with token", async ({ page }) => {
    // Mock the reset-password API
    await page.route("**/api/v1/auth/reset-password", async (route) => {
      await route.fulfill({
        status: 200,
        contentType: "application/json",
        body: JSON.stringify({
          status: "Success",
          message: "Your password has been reset.",
        }),
      });
    });

    // Simulate clicking a link with token
    await page.goto("/auth/reset-password?token=mock-token&email=test@example.com");
    
    await page.fill('input[id="password"]', "NewPassword123!");
    await page.fill('input[id="password_confirmation"]', "NewPassword123!");
    await page.click('button[type="submit"]');

    // Should redirect to login
    await expect(page).toHaveURL("/auth/login");
    await expect(page.getByText("Your password has been reset.")).toBeVisible();
  });
});

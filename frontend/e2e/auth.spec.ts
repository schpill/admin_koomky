import { test, expect } from "@playwright/test";

test.describe("Authentication", () => {
  test("user can login successfully", async ({ page }) => {
    // Mock the login API
    await page.route("**/api/v1/auth/login", async (route) => {
      await route.fulfill({
        status: 200,
        contentType: "application/json",
        body: JSON.stringify({
          status: "Success",
          message: "Login successful",
          data: {
            user: {
              id: "019c5cc8-d47d-7377-ad6e-7a0fe210d55b",
              name: "Test User",
              email: "test@example.com",
            },
            access_token: "mock-access-token",
            refresh_token: "mock-refresh-token",
          },
        }),
      });
    });

    // Go to login page
    await page.goto("/auth/login");

    // Fill form
    await page.fill('input[id="email"]', "test@example.com");
    await page.fill('input[id="password"]', "Password123!");

    // Submit
    await page.click('button[type="submit"]');

    // Should redirect to dashboard
    await expect(page).toHaveURL("/");
    await expect(page.locator("h1")).toContainText(
      /Dashboard|Tableau de bord/i
    );

    // Check if cookies are set
    const cookies = await page.context().cookies();
    const accessToken = cookies.find((c) => c.name === "koomky-access-token");
    expect(accessToken).toBeDefined();
    expect(accessToken?.value).toBe("mock-access-token");
  });
});

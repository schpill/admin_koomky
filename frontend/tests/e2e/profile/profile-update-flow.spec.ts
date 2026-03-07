import { expect, test } from "@playwright/test";

test.describe("Profile update flow", () => {
  test("updates the profile and refreshes the header avatar initials", async ({
    page,
  }) => {
    let currentProfile = {
      id: "user_1",
      name: "Test User",
      email: "test@example.com",
      avatar_url: null,
    };

    await page.context().addCookies([
      {
        name: "koomky-access-token",
        value: "valid-token",
        domain: "localhost",
        path: "/",
      },
    ]);

    await page.route("**/api/v1/profile", async (route) => {
      if (route.request().method() === "PATCH") {
        const formData = route.request().postDataBuffer();
        expect(formData?.toString()).toContain('name"\r\n\r\nNew User');
        currentProfile = {
          ...currentProfile,
          name: "New User",
          email: "new@example.com",
        };
      }

      await route.fulfill({
        status: 200,
        contentType: "application/json",
        body: JSON.stringify({
          status: "Success",
          message: "OK",
          data: currentProfile,
        }),
      });
    });

    await page.route("**/api/v1/auth/logout", async (route) => {
      await route.fulfill({
        status: 200,
        contentType: "application/json",
        body: JSON.stringify({
          status: "Success",
          message: "Logged out successfully",
          data: null,
        }),
      });
    });

    await page.goto("/profile");

    await page.getByLabel("Full name").fill("New User");
    await page.getByLabel("Email").fill("new@example.com");
    await page.getByRole("button", { name: "Save profile" }).click();

    await expect(page.getByText("Profile updated successfully")).toBeVisible();
    await expect(page.getByLabel("User menu")).toContainText("NU");
  });
});

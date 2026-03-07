import { expect, test } from "@playwright/test";

test.describe("Password change flow", () => {
  test("submits the password form and keeps the current session usable", async ({
    page,
  }) => {
    let passwordPayload: string | null = null;

    await page.context().addCookies([
      {
        name: "koomky-access-token",
        value: "valid-token",
        domain: "localhost",
        path: "/",
      },
    ]);

    await page.route("**/api/v1/profile", async (route) => {
      await route.fulfill({
        status: 200,
        contentType: "application/json",
        body: JSON.stringify({
          status: "Success",
          message: "OK",
          data: {
            id: "user_1",
            name: "Test User",
            email: "test@example.com",
            avatar_url: null,
          },
        }),
      });
    });

    await page.route("**/api/v1/profile/password", async (route) => {
      passwordPayload = route.request().postData();
      await route.fulfill({
        status: 200,
        contentType: "application/json",
        body: JSON.stringify({
          status: "Success",
          message: "Password updated successfully",
          data: null,
        }),
      });
    });

    await page.goto("/profile");

    await page.getByLabel("Current password").fill("CurrentPassword123!");
    await page.getByLabel("New password").fill("NewPassword123!");
    await page.getByLabel("Password confirmation").fill("NewPassword123!");
    await page.getByRole("button", { name: "Update password" }).click();

    await expect(page.getByText("Password updated successfully")).toBeVisible();
    await expect.poll(() => passwordPayload).toContain("current_password");
    await expect(
      page.getByRole("heading", { name: "My profile" })
    ).toBeVisible();
  });
});

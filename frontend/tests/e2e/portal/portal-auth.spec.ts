import { expect, test } from "@playwright/test";

test.describe("Portal auth", () => {
  test("request magic link", async ({ page }) => {
    let requestSeen = false;

    await page.route("**/api/v1/portal/auth/request", async (route) => {
      const payload = route.request().postDataJSON();
      requestSeen = payload?.email === "client@acme.test";

      await route.fulfill({
        status: 200,
        contentType: "application/json",
        body: JSON.stringify({
          status: "Success",
          message: "If the email exists, a magic link has been sent.",
          data: null,
        }),
      });
    });

    await page.goto("/portal/auth");

    const emailInput = page.locator("#portal-email");
    await emailInput.click();
    await emailInput.fill("client@acme.test");
    await expect(emailInput).toHaveValue("client@acme.test");
    await page.getByRole("button", { name: "Send magic link" }).click();

    await expect.poll(() => requestSeen).toBe(true);
    await expect(
      page.getByText("If the email exists, a magic link has been sent.")
    ).toBeVisible();
  });
});

import { expect, test } from "@playwright/test";
import { mockProtectedApi, seedAuthenticatedSession } from "../helpers/session";

test("invoice detail displays reminder panel with attach action", async ({
  page,
}) => {
  await seedAuthenticatedSession(page);
  await mockProtectedApi(page);

  await page.route("**/api/v1/invoices/inv_1", (route) =>
    route.fulfill({
      status: 200,
      contentType: "application/json",
      body: JSON.stringify({
        status: "Success",
        message: "OK",
        data: {
          id: "inv_1",
          client_id: "cli_1",
          number: "FAC-2026-0042",
          status: "overdue",
          issue_date: "2026-02-01",
          due_date: "2026-02-10",
          subtotal: 1000,
          tax_amount: 200,
          total: 1200,
          amount_paid: 0,
          currency: "EUR",
          line_items: [],
          payments: [],
          credit_notes: [],
          client: { id: "cli_1", name: "Client Test", email: "c@test.local" },
        },
      }),
    })
  );

  await page.route("**/api/v1/settings/portal", (route) =>
    route.fulfill({
      status: 200,
      contentType: "application/json",
      body: JSON.stringify({
        status: "Success",
        message: "OK",
        data: { payment_enabled: true },
      }),
    })
  );

  await page.route("**/api/v1/invoices/inv_1/reminder", (route) =>
    route.fulfill({
      status: 200,
      contentType: "application/json",
      body: JSON.stringify({ status: "Success", message: "OK", data: null }),
    })
  );

  await page.route("**/api/v1/reminder-sequences", (route) =>
    route.fulfill({
      status: 200,
      contentType: "application/json",
      body: JSON.stringify({
        status: "Success",
        message: "OK",
        data: [
          {
            id: "seq_1",
            user_id: "user_1",
            name: "Relance standard",
            description: null,
            is_active: true,
            is_default: true,
            steps: [],
          },
        ],
      }),
    })
  );

  await page.goto("/invoices/inv_1");

  await expect(page.getByRole("heading", { name: "FAC-2026-0042" })).toBeVisible();
  await expect(page.getByText("Relances")).toBeVisible();
  await expect(
    page.getByRole("button", { name: "Attacher une séquence" })
  ).toBeVisible();
});

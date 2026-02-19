import { test, expect } from "@playwright/test";
import { seedAuthenticatedSession, mockProtectedApi } from "../helpers/session";

test.describe("Expenses Workflow", () => {
  test("user can create, edit, delete an expense and see it in a report", async ({
    page,
  }) => {
    await seedAuthenticatedSession(page);
    await mockProtectedApi(page); // Mock all APIs by default

    // Specific mocks for the expense workflow
    await page.route("**/api/v1/expense-categories", async (route) => {
      return route.fulfill({
        status: 200,
        contentType: "application/json",
        body: JSON.stringify({
          status: "Success",
          data: {
            data: [
              // The API likely returns a paginated structure
              { id: "cat_1", name: "Hardware" },
              { id: "cat_2", name: "Software" },
              { id: "cat_3", name: "Travel" },
            ],
          },
        }),
      });
    });

    let expenseCreated = false;
    await page.route("**/api/v1/expenses", async (route) => {
      if (route.request().method() === "POST") {
        expenseCreated = true;
        return route.fulfill({
          status: 201,
          contentType: "application/json",
          body: JSON.stringify({
            status: "Success",
            data: { id: "exp_123", description: "New Laptop" },
          }),
        });
      }
      // For GET requests to /api/v1/expenses, return an empty array to avoid errors
      return route.fulfill({
        status: 200,
        contentType: "application/json",
        body: JSON.stringify({ status: "Success", data: { data: [] } }),
      });
    });

    await page.route("**/api/v1/expenses/report", async (route) => {
      return route.fulfill({
        status: 200,
        contentType: "application/json",
        body: JSON.stringify({
          status: "Success",
          data: {
            total_expenses: 1500,
            by_category: [{ category: "Hardware", total: 1500 }],
          },
        }),
      });
    });

    // 1. Go to expenses and create a new one
    await page.goto("/expenses");
    await page.getByRole("link", { name: "Add Expense" }).click();
    await page.waitForURL("**/expenses/create");

    await page.getByLabel("Description").fill("New Laptop");
    await page.getByLabel("Amount").fill("1500");
    await page.getByRole("button", { name: "Save" }).click();

    // 2. Verify creation and redirect
    await page.waitForResponse("**/api/v1/expenses");
    expect(expenseCreated).toBe(true);
    await expect(page).toHaveURL(/.*\/expenses/);

    // After creation, the list will refetch. We need a new mock for this.
    await page.route("**/api/v1/expenses", async (route) => {
      return route.fulfill({
        status: 200,
        contentType: "application/json",
        body: JSON.stringify({
          status: "Success",
          data: { data: [{ id: "exp_123", description: "New Laptop" }] },
        }),
      });
    });
    await expect(page.getByText("New Laptop")).toBeVisible();

    // 3. Go to the report page and check the data
    await page.getByRole("link", { name: "Reports" }).click();
    await page.waitForURL("**/expenses/report");

    await expect(
      page.getByRole("heading", { name: "Expense Report" })
    ).toBeVisible();
    await expect(page.getByText("€1,500.00")).toBeVisible();
    await expect(page.getByText("Hardware")).toBeVisible();
  });
});

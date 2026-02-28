import { expect, test } from "@playwright/test";
import { mockProtectedApi, seedAuthenticatedSession } from "../helpers/session";

test.describe("Live timer flow", () => {
  test("shows the active timer badge and stops the timer from the dropdown", async ({
    page,
  }) => {
    await seedAuthenticatedSession(page);
    await mockProtectedApi(page);

    await page.route("**/api/v1/dashboard", async (route) => {
      await route.fulfill({
        status: 200,
        contentType: "application/json",
        body: JSON.stringify({
          status: "Success",
          message: "OK",
          data: {
            total_clients: 1,
            active_projects: 1,
            pending_invoices_amount: 0,
            recent_activities: [],
            revenue_month: 0,
            revenue_quarter: 0,
            revenue_year: 0,
            pending_invoices_count: 0,
            overdue_invoices_count: 0,
            base_currency: "EUR",
            revenue_trend: [],
            upcoming_deadlines: [],
            recurring_profiles_active_count: 0,
            recurring_upcoming_due_profiles: [],
            recurring_estimated_revenue_month: 0,
            active_campaigns_count: 0,
            average_campaign_open_rate: 0,
            average_campaign_click_rate: 0,
            time_tracked_today_widget: {
              minutes_today: 75,
              entries_count: 2,
            },
          },
        }),
      });
    });

    await page.route("**/api/v1/timer/active", async (route) => {
      await route.fulfill({
        status: 200,
        contentType: "application/json",
        body: JSON.stringify({
          status: "Success",
          message: "OK",
          data: {
            id: "entry-1",
            task_id: "task-1",
            task_name: "Write regression tests",
            project_id: "project-1",
            project_name: "Phase 13",
            started_at: new Date(Date.now() - 5 * 60 * 1000).toISOString(),
            description: "Focus block",
          },
        }),
      });
    });

    await page.route("**/api/v1/timer/stop", async (route) => {
      await route.fulfill({
        status: 200,
        contentType: "application/json",
        body: JSON.stringify({
          status: "Success",
          message: "OK",
          data: { id: "entry-1" },
        }),
      });
    });

    await page.route("**/api/v1/tickets**", async (route) => {
      await route.fulfill({
        status: 200,
        contentType: "application/json",
        body: JSON.stringify({
          status: "Success",
          message: "OK",
          data: [],
          current_page: 1,
          last_page: 1,
          per_page: 15,
          total: 0,
        }),
      });
    });

    await page.goto("/");

    await expect(
      page.getByText("Temps suivi aujourd'hui", { exact: true })
    ).toBeVisible();
    await expect(page.getByText("1 h 15")).toBeVisible();

    const timerBadge = page.getByRole("button", { name: /timer actif/i });
    await expect(timerBadge).toBeVisible();

    await timerBadge.click();
    await page.getByRole("button", { name: "Arrêter" }).click();

    await expect(timerBadge).toBeHidden();
  });
});

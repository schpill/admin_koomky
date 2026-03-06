import { expect, test } from "@playwright/test";
import { seedAuthenticatedSession } from "../helpers/session";

test("workflow detail page renders a persisted workflow", async ({ page }) => {
  await seedAuthenticatedSession(page);

  await page.route("**/api/v1/workflows/wf_1", async (route) => {
    await route.fulfill({
      status: 200,
      contentType: "application/json",
      body: JSON.stringify({
        status: "Success",
        data: {
          id: "wf_1",
          name: "Lifecycle workflow",
          description: "Workflow details",
          trigger_type: "manual",
          trigger_config: {},
          status: "draft",
          entry_step_id: "step_1",
          steps: [
            {
              id: "step_1",
              type: "send_email",
              config: { subject: "Hello", content: "World" },
              next_step_id: "step_2",
              else_step_id: null,
              position_x: 0,
              position_y: 0,
            },
            {
              id: "step_2",
              type: "end",
              config: {},
              next_step_id: null,
              else_step_id: null,
              position_x: 240,
              position_y: 0,
            },
          ],
          enrollments: [],
          analytics: {
            active_enrollments: 0,
            completion_rate: 0,
            dropoff_by_step: [],
          },
        },
      }),
    });
  });

  await page.route("**/api/v1/workflows/wf_1/enrollments**", async (route) => {
    await route.fulfill({
      status: 200,
      contentType: "application/json",
      body: JSON.stringify({
        status: "Success",
        data: {
          data: [],
        },
      }),
    });
  });

  await page.goto("/campaigns/workflows/wf_1");
  await expect(
    page.getByRole("heading", { name: "Lifecycle workflow" })
  ).toBeVisible();
});

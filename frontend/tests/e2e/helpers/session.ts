import type { Page, Route } from "@playwright/test";

function json(data: unknown): string {
  return JSON.stringify({
    status: "Success",
    message: "OK",
    data,
  });
}

async function fulfillJson(route: Route, data: unknown): Promise<void> {
  await route.fulfill({
    status: 200,
    contentType: "application/json",
    body: json(data),
  });
}

export async function seedAuthenticatedSession(page: Page): Promise<void> {
  await page.context().addCookies([
    {
      name: "koomky-access-token",
      value: "e2e-token",
      domain: "localhost",
      path: "/",
    },
  ]);
}

export async function mockProtectedApi(page: Page): Promise<void> {
  await page.route("**/api/v1/**", async (route) => {
    const request = route.request();
    const url = new URL(request.url());
    const method = request.method();
    const path = url.pathname;

    if (path.endsWith("/dashboard")) {
      return fulfillJson(route, {
        total_clients: 2,
        active_projects: 1,
        pending_invoices_amount: 300,
        recent_activities: [],
        revenue_month: 300,
        revenue_quarter: 300,
        revenue_year: 300,
        pending_invoices_count: 1,
        overdue_invoices_count: 0,
        revenue_trend: [
          { month: "2026-01", total: 100 },
          { month: "2026-02", total: 200 },
        ],
        upcoming_deadlines: [],
        active_campaigns_count: 1,
        average_campaign_open_rate: 42.5,
        average_campaign_click_rate: 7.2,
      });
    }

    if (path.endsWith("/clients")) {
      return fulfillJson(route, {
        data: [{ id: "cli_1", name: "Acme", status: "active" }],
        current_page: 1,
        last_page: 1,
        per_page: 15,
        total: 1,
      });
    }

    if (path.endsWith("/projects")) {
      return fulfillJson(route, {
        data: [{ id: "prj_1", name: "Website", status: "in_progress" }],
        current_page: 1,
        last_page: 1,
        per_page: 15,
        total: 1,
      });
    }

    if (path.endsWith("/invoices")) {
      return fulfillJson(route, {
        data: [
          {
            id: "inv_1",
            client_id: "cli_1",
            number: "INV-001",
            status: "draft",
            issue_date: "2026-02-01",
            due_date: "2026-02-15",
            total: 120,
            line_items: [
              {
                description: "Design",
                quantity: 1,
                unit_price: 120,
                vat_rate: 0,
                total: 120,
              },
            ],
          },
        ],
        current_page: 1,
        last_page: 1,
        per_page: 15,
        total: 1,
      });
    }

    if (path.endsWith("/segments") && method === "GET") {
      return fulfillJson(route, {
        data: [
          {
            id: "seg_1",
            name: "VIP clients",
            description: "High value",
            filters: {
              group_boolean: "and",
              criteria_boolean: "or",
              groups: [
                {
                  criteria: [
                    { type: "tag", operator: "equals", value: "vip" },
                  ],
                },
              ],
            },
            contact_count: 1,
            cached_contact_count: 1,
          },
        ],
        current_page: 1,
        last_page: 1,
        per_page: 15,
        total: 1,
      });
    }

    if (path.endsWith("/segments") && method === "POST") {
      return fulfillJson(route, {
        id: "seg_1",
        name: "VIP clients",
        description: "High value",
        filters: {
          group_boolean: "and",
          criteria_boolean: "or",
          groups: [
            {
              criteria: [{ type: "tag", operator: "equals", value: "vip" }],
            },
          ],
        },
        contact_count: 1,
      });
    }

    if (/\/segments\/[^/]+$/.test(path) && method === "GET") {
      return fulfillJson(route, {
        id: "seg_1",
        name: "VIP clients",
        description: "High value",
        filters: {
          group_boolean: "and",
          criteria_boolean: "or",
          groups: [
            {
              criteria: [{ type: "tag", operator: "equals", value: "vip" }],
            },
          ],
        },
        contact_count: 1,
      });
    }

    if (/\/segments\/[^/]+\/preview$/.test(path)) {
      return fulfillJson(route, {
        segment_id: "seg_1",
        total_matching: 1,
        cached_contact_count: 1,
        contacts: {
          data: [
            {
              id: "ct_1",
              first_name: "Alice",
              last_name: "Doe",
              email: "alice@example.com",
              phone: "+33123456789",
              client: { id: "cli_1", name: "Acme" },
            },
          ],
          current_page: 1,
          last_page: 1,
          per_page: 15,
          total: 1,
        },
      });
    }

    if (path.endsWith("/campaign-templates") && method === "GET") {
      return fulfillJson(route, [
        {
          id: "tpl_1",
          name: "Newsletter",
          type: "email",
          subject: "Hello {{first_name}}",
          content: "Welcome {{first_name}}",
        },
      ]);
    }

    if (path.endsWith("/campaigns") && method === "GET") {
      return fulfillJson(route, {
        data: [
          {
            id: "camp_1",
            name: "Spring launch",
            type: "email",
            status: "draft",
            content: "Hello {{first_name}}",
            subject: "Hi",
            recipients_count: 1,
          },
        ],
        current_page: 1,
        last_page: 1,
        per_page: 15,
        total: 1,
      });
    }

    if (path.endsWith("/campaigns") && method === "POST") {
      return fulfillJson(route, {
        id: "camp_1",
        name: "Spring launch",
        type: "email",
        status: "draft",
        content: "Hello {{first_name}}",
        subject: "Hi",
        recipients: [],
      });
    }

    if (/\/campaigns\/compare$/.test(path)) {
      return fulfillJson(route, [
        {
          campaign_id: "camp_1",
          campaign_name: "Spring launch",
          total_recipients: 10,
          open_rate: 45,
          click_rate: 8,
          time_series: [],
        },
        {
          campaign_id: "camp_2",
          campaign_name: "SMS promo",
          total_recipients: 12,
          open_rate: 0,
          click_rate: 0,
          time_series: [],
        },
      ]);
    }

    if (/\/campaigns\/[^/]+\/analytics\/export$/.test(path)) {
      await route.fulfill({
        status: 200,
        contentType: "text/csv",
        body: "metric,value\nopen_rate,45\nclick_rate,8\n",
      });
      return;
    }

    if (/\/campaigns\/[^/]+\/analytics$/.test(path)) {
      return fulfillJson(route, {
        campaign_id: "camp_1",
        campaign_name: "Spring launch",
        total_recipients: 10,
        sent_count: 10,
        delivered_count: 10,
        open_rate: 45,
        click_rate: 8,
        bounced_count: 0,
        time_series: [{ hour: "2026-02-17 10:00:00", opens: 5, clicks: 1 }],
      });
    }

    if (/\/campaigns\/[^/]+$/.test(path) && method === "GET") {
      return fulfillJson(route, {
        id: "camp_1",
        name: "Spring launch",
        type: "email",
        status: "draft",
        content: "Hello {{first_name}}",
        subject: "Hi",
        recipients: [
          {
            id: "rcp_1",
            status: "sent",
            sent_at: "2026-02-17 10:00:00",
            contact: { first_name: "Alice", last_name: "Doe" },
            email: "alice@example.com",
          },
        ],
      });
    }

    if (/\/campaigns\/[^/]+\/(send|pause|duplicate|test)$/.test(path)) {
      return fulfillJson(route, {
        id: "camp_1",
        name: "Spring launch",
        type: "email",
        status: "sending",
        content: "Hello {{first_name}}",
      });
    }

    if (/\/settings\/(email|sms|notifications)$/.test(path)) {
      return fulfillJson(route, { id: "user_1" });
    }

    return fulfillJson(route, {});
  });
}

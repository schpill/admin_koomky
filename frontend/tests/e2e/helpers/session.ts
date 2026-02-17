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
        base_currency: "EUR",
        pending_invoices_count: 1,
        overdue_invoices_count: 0,
        revenue_trend: [
          { month: "2026-01", total: 100 },
          { month: "2026-02", total: 200 },
        ],
        upcoming_deadlines: [],
        recurring_profiles_active_count: 1,
        recurring_upcoming_due_profiles: [
          {
            id: "rip_1",
            name: "Monthly retainer",
            frequency: "monthly",
            next_due_date: "2026-02-20",
            client_name: "Acme",
          },
        ],
        recurring_estimated_revenue_month: 1200,
        active_campaigns_count: 1,
        average_campaign_open_rate: 42.5,
        average_campaign_click_rate: 7.2,
      });
    }

    if (path.endsWith("/currencies") && method === "GET") {
      return fulfillJson(route, [
        {
          id: "cur_eur",
          code: "EUR",
          name: "Euro",
          symbol: "EUR",
          decimal_places: 2,
          is_active: true,
        },
        {
          id: "cur_usd",
          code: "USD",
          name: "US Dollar",
          symbol: "USD",
          decimal_places: 2,
          is_active: true,
        },
        {
          id: "cur_jpy",
          code: "JPY",
          name: "Japanese Yen",
          symbol: "JPY",
          decimal_places: 0,
          is_active: true,
        },
      ]);
    }

    if (path.endsWith("/currencies/rates") && method === "GET") {
      return fulfillJson(route, {
        base_currency: "EUR",
        rates: {
          USD: 1.1,
          JPY: 160,
        },
      });
    }

    if (path.endsWith("/settings/currency") && method === "PUT") {
      return fulfillJson(route, {
        id: "user_1",
        base_currency: "EUR",
        exchange_rate_provider: "open_exchange_rates",
      });
    }

    if (path.endsWith("/calendar-events") && method === "GET") {
      return fulfillJson(route, [
        {
          id: "evt_1",
          title: "Sprint planning",
          description: "Weekly planning",
          start_at: "2026-03-10 09:00:00",
          end_at: "2026-03-10 10:00:00",
          all_day: false,
          location: "Remote",
          type: "meeting",
          sync_status: "synced",
        },
      ]);
    }

    if (path.endsWith("/calendar-events") && method === "POST") {
      return fulfillJson(route, {
        id: "evt_2",
        title: "Created event",
        description: "",
        start_at: "2026-03-11 09:00:00",
        end_at: "2026-03-11 10:00:00",
        all_day: false,
        location: "Remote",
        type: "meeting",
        sync_status: "local",
      });
    }

    if (/\/calendar-events\/[^/]+$/.test(path) && method === "PUT") {
      return fulfillJson(route, {
        id: "evt_1",
        title: "Updated event",
        description: "Updated",
        start_at: "2026-03-10 09:00:00",
        end_at: "2026-03-10 10:00:00",
        all_day: false,
        location: "Remote",
        type: "meeting",
        sync_status: "local",
      });
    }

    if (/\/calendar-events\/[^/]+$/.test(path) && method === "DELETE") {
      return fulfillJson(route, {});
    }

    if (path.endsWith("/calendar-connections") && method === "GET") {
      return fulfillJson(route, [
        {
          id: "conn_1",
          provider: "google",
          name: "Google Work",
          calendar_id: "primary",
          sync_enabled: true,
          last_synced_at: "2026-03-01 10:00:00",
        },
      ]);
    }

    if (path.endsWith("/calendar-connections") && method === "POST") {
      return fulfillJson(route, {
        id: "conn_2",
        provider: "google",
        name: "Google Personal",
        calendar_id: "primary",
        sync_enabled: true,
      });
    }

    if (/\/calendar-connections\/[^/]+$/.test(path) && method === "PUT") {
      return fulfillJson(route, {
        id: "conn_1",
        provider: "google",
        name: "Google Work",
        calendar_id: "primary",
        sync_enabled: false,
      });
    }

    if (/\/calendar-connections\/[^/]+$/.test(path) && method === "DELETE") {
      return fulfillJson(route, {});
    }

    if (path.endsWith("/clients")) {
      return fulfillJson(route, {
        data: [
          {
            id: "cli_1",
            name: "Acme",
            status: "active",
            preferred_currency: "USD",
          },
        ],
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
            currency: "EUR",
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

    if (path.endsWith("/recurring-invoices") && method === "GET") {
      return fulfillJson(route, {
        data: [
          {
            id: "rip_1",
            client_id: "cli_1",
            name: "Monthly retainer",
            frequency: "monthly",
            start_date: "2026-02-01",
            next_due_date: "2026-02-20",
            line_items: [
              {
                description: "Retainer",
                quantity: 1,
                unit_price: 1200,
                vat_rate: 20,
              },
            ],
            payment_terms_days: 30,
            discount_percent: 0,
            auto_send: true,
            status: "active",
            occurrences_generated: 2,
            currency: "EUR",
            client: { id: "cli_1", name: "Acme" },
            generated_invoices: [],
          },
        ],
        current_page: 1,
        last_page: 1,
        per_page: 15,
        total: 1,
      });
    }

    if (path.endsWith("/recurring-invoices") && method === "POST") {
      return fulfillJson(route, {
        id: "rip_1",
        client_id: "cli_1",
        name: "Monthly retainer",
        frequency: "monthly",
        start_date: "2026-02-01",
        next_due_date: "2026-02-20",
        line_items: [
          {
            description: "Retainer",
            quantity: 1,
            unit_price: 1200,
            vat_rate: 20,
          },
        ],
        payment_terms_days: 30,
        discount_percent: 0,
        auto_send: true,
        status: "active",
        occurrences_generated: 0,
        currency: "EUR",
      });
    }

    if (/\/recurring-invoices\/[^/]+$/.test(path) && method === "GET") {
      return fulfillJson(route, {
        id: "rip_1",
        client_id: "cli_1",
        name: "Monthly retainer",
        frequency: "monthly",
        start_date: "2026-02-01",
        next_due_date: "2026-02-20",
        line_items: [
          {
            description: "Retainer",
            quantity: 1,
            unit_price: 1200,
            vat_rate: 20,
          },
        ],
        payment_terms_days: 30,
        discount_percent: 0,
        auto_send: true,
        status: "active",
        occurrences_generated: 2,
        currency: "EUR",
        client: { id: "cli_1", name: "Acme" },
        generated_invoices: [
          {
            id: "inv_1",
            number: "FAC-2026-0001",
            issue_date: "2026-02-01",
            due_date: "2026-03-03",
            total: 1440,
            status: "draft",
          },
        ],
      });
    }

    if (/\/recurring-invoices\/[^/]+$/.test(path) && method === "PUT") {
      return fulfillJson(route, {
        id: "rip_1",
        client_id: "cli_1",
        name: "Updated profile",
        frequency: "monthly",
        start_date: "2026-02-01",
        next_due_date: "2026-02-20",
        line_items: [
          {
            description: "Retainer",
            quantity: 1,
            unit_price: 1200,
            vat_rate: 20,
          },
        ],
        payment_terms_days: 30,
        discount_percent: 0,
        auto_send: true,
        status: "active",
        occurrences_generated: 2,
        currency: "EUR",
      });
    }

    if (/\/recurring-invoices\/[^/]+$/.test(path) && method === "DELETE") {
      return fulfillJson(route, {});
    }

    if (/\/recurring-invoices\/[^/]+\/(pause|resume|cancel)$/.test(path)) {
      const action = path.split("/").pop();
      const statusMap: Record<string, string> = {
        pause: "paused",
        resume: "active",
        cancel: "cancelled",
      };

      return fulfillJson(route, {
        id: "rip_1",
        client_id: "cli_1",
        name: "Monthly retainer",
        frequency: "monthly",
        start_date: "2026-02-01",
        next_due_date: "2026-02-20",
        line_items: [
          {
            description: "Retainer",
            quantity: 1,
            unit_price: 1200,
            vat_rate: 20,
          },
        ],
        payment_terms_days: 30,
        discount_percent: 0,
        auto_send: true,
        status: statusMap[action || "resume"] || "active",
        occurrences_generated: 2,
        currency: "EUR",
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
                  criteria: [{ type: "tag", operator: "equals", value: "vip" }],
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

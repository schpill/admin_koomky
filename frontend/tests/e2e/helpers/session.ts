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
  const calendarEvents: Array<Record<string, unknown>> = [
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
  ];

  const calendarConnections: Array<Record<string, unknown>> = [
    {
      id: "conn_1",
      provider: "google",
      name: "Google Work",
      calendar_id: "primary",
      sync_enabled: true,
      last_synced_at: "2026-03-01 10:00:00",
    },
  ];

  let calendarConnectionIndex = 2;
  let calendarEventIndex = 2;
  let calendarAutoEvents = {
    project_deadlines: true,
    task_due_dates: true,
    invoice_reminders: true,
  };

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
        profit_loss_summary: {
          revenue: 1200,
          expenses: 450,
          profit: 750,
          margin: 62.5,
          base_currency: "EUR",
        },
        expense_overview: {
          month_total: 450,
          billable_total: 220,
          non_billable_total: 230,
          base_currency: "EUR",
          top_categories: [
            { category: "Travel", total: 200, count: 3 },
            { category: "Software", total: 150, count: 2 },
          ],
        },
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
      return fulfillJson(route, calendarEvents);
    }

    if (path.endsWith("/calendar-events") && method === "POST") {
      const payload = request.postDataJSON() as Record<string, unknown>;
      const created = {
        id: `evt_${calendarEventIndex++}`,
        title: String(payload.title || "Created event"),
        description: payload.description ? String(payload.description) : "",
        start_at: String(payload.start_at || "2026-03-11 09:00:00"),
        end_at: String(payload.end_at || "2026-03-11 10:00:00"),
        all_day: Boolean(payload.all_day || false),
        location: payload.location ? String(payload.location) : "Remote",
        type: String(payload.type || "meeting"),
        sync_status: "local",
      };
      calendarEvents.push(created);
      return fulfillJson(route, created);
    }

    if (/\/calendar-events\/[^/]+$/.test(path) && method === "PUT") {
      const eventId = path.split("/").pop() || "";
      const payload = request.postDataJSON() as Record<string, unknown>;
      const eventIndex = calendarEvents.findIndex(
        (item) => item.id === eventId
      );
      const existing =
        eventIndex >= 0
          ? calendarEvents[eventIndex]
          : { id: eventId, sync_status: "local" };
      const updated = {
        ...existing,
        ...payload,
        id: eventId,
        sync_status: "local",
      };

      if (eventIndex >= 0) {
        calendarEvents[eventIndex] = updated;
      } else {
        calendarEvents.push(updated);
      }

      return fulfillJson(route, updated);
    }

    if (/\/calendar-events\/[^/]+$/.test(path) && method === "DELETE") {
      const eventId = path.split("/").pop() || "";
      const index = calendarEvents.findIndex((item) => item.id === eventId);
      if (index >= 0) {
        calendarEvents.splice(index, 1);
      }

      return fulfillJson(route, {});
    }

    if (path.endsWith("/calendar-connections") && method === "GET") {
      return fulfillJson(route, calendarConnections);
    }

    if (path.endsWith("/calendar-connections") && method === "POST") {
      const payload = request.postDataJSON() as Record<string, unknown>;
      const created = {
        id: `conn_${calendarConnectionIndex++}`,
        provider: String(payload.provider || "google"),
        name: String(payload.name || "Google Personal"),
        calendar_id: String(payload.calendar_id || "primary"),
        sync_enabled: payload.sync_enabled !== false,
      };
      calendarConnections.push(created);
      return fulfillJson(route, created);
    }

    if (/\/calendar-connections\/[^/]+$/.test(path) && method === "PUT") {
      const connectionId = path.split("/").pop() || "";
      const payload = request.postDataJSON() as Record<string, unknown>;
      const connectionIndex = calendarConnections.findIndex(
        (item) => item.id === connectionId
      );
      const existing =
        connectionIndex >= 0
          ? calendarConnections[connectionIndex]
          : { id: connectionId, provider: "google", name: "Calendar" };
      const updated = {
        ...existing,
        ...payload,
        id: connectionId,
      };

      if (connectionIndex >= 0) {
        calendarConnections[connectionIndex] = updated;
      } else {
        calendarConnections.push(updated);
      }

      return fulfillJson(route, updated);
    }

    if (/\/calendar-connections\/[^/]+$/.test(path) && method === "DELETE") {
      const connectionId = path.split("/").pop() || "";
      const index = calendarConnections.findIndex(
        (item) => item.id === connectionId
      );
      if (index >= 0) {
        calendarConnections.splice(index, 1);
      }

      return fulfillJson(route, {});
    }

    if (path.endsWith("/settings/calendar") && method === "GET") {
      return fulfillJson(route, {
        auto_events: calendarAutoEvents,
      });
    }

    if (path.endsWith("/settings/calendar") && method === "PUT") {
      const payload = request.postDataJSON() as {
        auto_events?: typeof calendarAutoEvents;
      };
      if (payload.auto_events) {
        calendarAutoEvents = {
          ...calendarAutoEvents,
          ...payload.auto_events,
        };
      }

      return fulfillJson(route, {
        auto_events: calendarAutoEvents,
      });
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

    if (/\/projects\/[^/]+$/.test(path) && method === "GET") {
      const projectId = path.split("/").pop() || "prj_1";
      return fulfillJson(route, {
        id: projectId,
        reference: "PRJ-2026-0001",
        name: "Website redesign",
        status: "in_progress",
        billing_type: "hourly",
        hourly_rate: 100,
        currency: "EUR",
        client: { id: "cli_1", name: "Acme" },
      });
    }

    if (/\/projects\/[^/]+\/tasks$/.test(path) && method === "GET") {
      return fulfillJson(route, [
        {
          id: "task_1",
          project_id: "prj_1",
          title: "Planning",
          description: "",
          status: "todo",
          priority: "medium",
          sort_order: 1,
        },
      ]);
    }

    if (/\/projects\/[^/]+\/expenses$/.test(path) && method === "GET") {
      return fulfillJson(route, [
        {
          id: "exp_1",
          description: "Allocated expense",
          date: "2026-02-11",
          amount: 120,
          currency: "EUR",
          status: "approved",
          is_billable: true,
        },
      ]);
    }

    if (
      /\/projects\/[^/]+\/generate-invoice$/.test(path) &&
      method === "POST"
    ) {
      return fulfillJson(route, {
        id: "inv_2",
        number: "FAC-2026-0002",
        status: "draft",
        line_items: [
          {
            description: "Time entries - Planning",
            quantity: 2,
            unit_price: 100,
            vat_rate: 20,
            total: 200,
          },
        ],
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

    if (/\/invoices\/[^/]+$/.test(path) && method === "GET") {
      const invoiceId = path.split("/").pop() || "inv_1";
      return fulfillJson(route, {
        id: invoiceId,
        client_id: "cli_1",
        number: "INV-001",
        status: "sent",
        issue_date: "2026-02-01",
        due_date: "2026-02-15",
        subtotal: 100,
        tax_amount: 20,
        total: 120,
        amount_paid: 0,
        balance_due: 120,
        currency: "EUR",
        client: { id: "cli_1", name: "Acme", email: "billing@acme.test" },
        line_items: [
          {
            description: "Design",
            quantity: 1,
            unit_price: 100,
            vat_rate: 20,
            total: 120,
          },
        ],
        payments: [],
        credit_notes: [],
      });
    }

    if (path.endsWith("/expense-categories") && method === "GET") {
      return fulfillJson(route, [
        {
          id: "cat_1",
          name: "Travel",
          color: "#2459ff",
          icon: "plane",
          is_default: true,
        },
        {
          id: "cat_2",
          name: "Software",
          color: "#10b981",
          icon: "monitor",
          is_default: false,
        },
      ]);
    }

    if (path.endsWith("/expense-categories") && method === "POST") {
      const payload = request.postDataJSON() as Record<string, unknown>;
      return fulfillJson(route, {
        id: "cat_new",
        name: String(payload.name || "Category"),
        color: String(payload.color || "#2459ff"),
        icon: String(payload.icon || "tag"),
        is_default: false,
      });
    }

    if (/\/expense-categories\/[^/]+$/.test(path) && method === "PUT") {
      const categoryId = path.split("/").pop() || "cat_2";
      const payload = request.postDataJSON() as Record<string, unknown>;
      return fulfillJson(route, {
        id: categoryId,
        name: String(payload.name || "Category"),
        color: String(payload.color || "#2459ff"),
        icon: String(payload.icon || "tag"),
        is_default: false,
      });
    }

    if (/\/expense-categories\/[^/]+$/.test(path) && method === "DELETE") {
      return fulfillJson(route, {});
    }

    if (path.endsWith("/expenses") && method === "GET") {
      return fulfillJson(route, {
        data: [
          {
            id: "exp_1",
            expense_category_id: "cat_1",
            project_id: "prj_1",
            client_id: "cli_1",
            description: "Train ticket",
            amount: 120,
            currency: "EUR",
            date: "2026-02-11",
            payment_method: "card",
            is_billable: true,
            is_reimbursable: false,
            status: "approved",
            category: { id: "cat_1", name: "Travel" },
            project: {
              id: "prj_1",
              name: "Website",
              reference: "PRJ-2026-0001",
            },
            client: { id: "cli_1", name: "Acme" },
            receipt_path: "receipts/exp_1.pdf",
            receipt_mime_type: "application/pdf",
            receipt_filename: "exp_1.pdf",
          },
        ],
        current_page: 1,
        last_page: 1,
        per_page: 50,
        total: 1,
      });
    }

    if (path.endsWith("/expenses") && method === "POST") {
      const payload = request.postDataJSON() as Record<string, unknown>;
      return fulfillJson(route, {
        id: "exp_new",
        description: String(payload.description || "Expense"),
        amount: Number(payload.amount || 0),
        currency: String(payload.currency || "EUR"),
        date: String(payload.date || "2026-02-11"),
        status: String(payload.status || "pending"),
        is_billable: Boolean(payload.is_billable),
        is_reimbursable: Boolean(payload.is_reimbursable),
      });
    }

    if (/\/expenses\/[^/]+$/.test(path) && method === "GET") {
      const expenseId = path.split("/").pop() || "exp_1";
      return fulfillJson(route, {
        id: expenseId,
        expense_category_id: "cat_1",
        project_id: "prj_1",
        client_id: "cli_1",
        description: "Train ticket",
        amount: 120,
        currency: "EUR",
        date: "2026-02-11",
        payment_method: "card",
        is_billable: true,
        is_reimbursable: false,
        status: "approved",
        category: { id: "cat_1", name: "Travel" },
        project: { id: "prj_1", name: "Website", reference: "PRJ-2026-0001" },
        client: { id: "cli_1", name: "Acme" },
        receipt_path: "receipts/exp_1.pdf",
        receipt_mime_type: "application/pdf",
        receipt_filename: "exp_1.pdf",
      });
    }

    if (/\/expenses\/[^/]+$/.test(path) && method === "PUT") {
      const expenseId = path.split("/").pop() || "exp_1";
      const payload = request.postDataJSON() as Record<string, unknown>;
      return fulfillJson(route, {
        id: expenseId,
        ...payload,
      });
    }

    if (/\/expenses\/[^/]+$/.test(path) && method === "DELETE") {
      return fulfillJson(route, {});
    }

    if (/\/expenses\/[^/]+\/receipt$/.test(path) && method === "POST") {
      const expenseId = path.split("/")[path.split("/").length - 2] || "exp_1";
      return fulfillJson(route, {
        id: expenseId,
        description: "Train ticket",
        amount: 120,
        currency: "EUR",
        receipt_path: "receipts/uploaded.pdf",
        receipt_mime_type: "application/pdf",
        receipt_filename: "uploaded.pdf",
      });
    }

    if (/\/expenses\/[^/]+\/receipt$/.test(path) && method === "GET") {
      await route.fulfill({
        status: 200,
        contentType: "application/pdf",
        body: "%PDF-1.4",
      });
      return;
    }

    if (path.endsWith("/expenses/report") && method === "GET") {
      return fulfillJson(route, {
        filters: {},
        base_currency: "EUR",
        total_expenses: 450,
        tax_total: 75,
        count: 3,
        billable_split: { billable: 220, non_billable: 230 },
        by_category: [
          { category: "Travel", total: 200, count: 2 },
          { category: "Software", total: 250, count: 1 },
        ],
        by_project: [
          {
            project_reference: "PRJ-2026-0001",
            project_name: "Website redesign",
            total: 300,
            count: 2,
          },
        ],
        by_month: [{ month: "2026-02", total: 450 }],
        items: [],
      });
    }

    if (path.endsWith("/expenses/report/export") && method === "GET") {
      await route.fulfill({
        status: 200,
        contentType: "text/csv",
        body: "date,description,amount\\n2026-02-11,Train ticket,120\\n",
      });
      return;
    }

    if (path.endsWith("/reports/profit-loss") && method === "GET") {
      return fulfillJson(route, {
        base_currency: "EUR",
        revenue: 1200,
        expenses: 450,
        profit: 750,
        margin: 62.5,
        by_month: [
          { month: "2026-01", revenue: 500, expenses: 200, profit: 300 },
          { month: "2026-02", revenue: 700, expenses: 250, profit: 450 },
        ],
        by_project: [
          {
            project_id: "prj_1",
            project_name: "Website redesign",
            project_reference: "PRJ-2026-0001",
            revenue: 1200,
            expenses: 450,
            profit: 750,
          },
        ],
        by_client: [
          {
            client_id: "cli_1",
            client_name: "Acme",
            revenue: 1200,
            expenses: 450,
            profit: 750,
          },
        ],
      });
    }

    if (path.endsWith("/reports/project-profitability") && method === "GET") {
      return fulfillJson(route, [
        {
          project_id: "prj_1",
          project_reference: "PRJ-2026-0001",
          project_name: "Website redesign",
          client_name: "Acme",
          revenue: 1200,
          time_cost: 300,
          expenses: 250,
          profit: 650,
          margin: 54.17,
          currency: "EUR",
        },
      ]);
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

    if (path.endsWith("/settings/portal") && method === "GET") {
      return fulfillJson(route, {
        portal_enabled: true,
        custom_logo: "",
        custom_color: "#2459ff",
        welcome_message: "Welcome to your portal",
        payment_enabled: true,
        quote_acceptance_enabled: true,
        stripe_publishable_key: "pk_test_123",
        stripe_secret_key: "sk_test_123",
        stripe_webhook_secret: "whsec_test_123",
        payment_methods_enabled: ["card"],
      });
    }

    if (path.endsWith("/settings/portal") && method === "PUT") {
      const payload = request.postDataJSON() as Record<string, unknown>;
      return fulfillJson(route, payload);
    }

    if (/\/clients\/[^/]+\/portal-access$/.test(path) && method === "GET") {
      return fulfillJson(route, [
        {
          id: "pat_1",
          email: "client@acme.test",
          token: "tok_xxx",
          expires_at: "2026-03-01T10:00:00Z",
          is_active: true,
          last_used_at: null,
          created_at: "2026-02-01T10:00:00Z",
        },
      ]);
    }

    if (/\/clients\/[^/]+\/portal-access$/.test(path) && method === "POST") {
      return fulfillJson(route, {
        id: "pat_new",
        email: "client@acme.test",
        token: "tok_new",
        expires_at: "2026-03-01T10:00:00Z",
        is_active: true,
        created_at: "2026-02-20T10:00:00Z",
      });
    }

    if (
      /\/clients\/[^/]+\/portal-access\/[^/]+$/.test(path) &&
      method === "DELETE"
    ) {
      return fulfillJson(route, {});
    }

    if (/\/clients\/[^/]+\/portal-activity$/.test(path) && method === "GET") {
      return fulfillJson(route, {
        data: [
          {
            id: "pal_1",
            action: "login",
            ip_address: "127.0.0.1",
            created_at: "2026-02-20T09:00:00Z",
          },
        ],
        current_page: 1,
        last_page: 1,
        per_page: 15,
        total: 1,
      });
    }

    if (/\/settings\/(email|sms|notifications)$/.test(path)) {
      return fulfillJson(route, { id: "user_1" });
    }

    return fulfillJson(route, {});
  });
}

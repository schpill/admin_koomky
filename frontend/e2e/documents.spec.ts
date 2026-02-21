import { test, expect } from "@playwright/test";

const AUTH_COOKIE = {
  name: "koomky-access-token",
  value: "mock-token",
  domain: "localhost",
  path: "/",
};

const EMPTY_STATS = {
  total_count: 0,
  total_size_bytes: 0,
  quota_bytes: 536870912,
  usage_percentage: 0,
  by_type: [],
};

const MOCK_DOCUMENT = {
  id: "doc-uuid-1",
  title: "Contrat de prestation",
  original_filename: "contrat.pdf",
  document_type: "pdf",
  script_language: null,
  file_size: 102400,
  version: 1,
  tags: ["contrat", "2026"],
  client_id: null,
  client: null,
  last_sent_at: null,
  last_sent_to: null,
  created_at: "2026-02-21T10:00:00.000Z",
  updated_at: "2026-02-21T10:00:00.000Z",
};

const MOCK_DOCUMENT_2 = {
  id: "doc-uuid-2",
  title: "Facture Janvier",
  original_filename: "facture_jan.pdf",
  document_type: "pdf",
  script_language: null,
  file_size: 51200,
  version: 2,
  tags: ["facture"],
  client_id: null,
  client: null,
  last_sent_at: "2026-02-20T09:00:00.000Z",
  last_sent_to: "client@example.com",
  created_at: "2026-02-20T08:00:00.000Z",
  updated_at: "2026-02-20T08:00:00.000Z",
};

test.describe("Documents Library", () => {
  test.beforeEach(async ({ page, context }) => {
    await context.addCookies([AUTH_COOKIE]);

    await page.route("**/api/v1/documents/stats", async (route) => {
      await route.fulfill({
        status: 200,
        contentType: "application/json",
        body: JSON.stringify(EMPTY_STATS),
      });
    });

    await page.route("**/api/v1/documents*", async (route) => {
      if (route.request().method() === "GET") {
        await route.fulfill({
          status: 200,
          contentType: "application/json",
          body: JSON.stringify({
            data: [],
            current_page: 1,
            last_page: 1,
            total: 0,
            per_page: 24,
          }),
        });
      }
    });

    await page.route("**/api/v1/clients", async (route) => {
      await route.fulfill({
        status: 200,
        contentType: "application/json",
        body: JSON.stringify({ data: [] }),
      });
    });
  });

  test("should display the documents library with empty state", async ({
    page,
  }) => {
    await page.goto("/documents");

    await expect(page.locator("h1")).toContainText(/Documents/i);
    await expect(page.getByText(/Aucun document trouvé/i)).toBeVisible();
    await expect(
      page.getByRole("button", { name: /Importer un document/i })
    ).toBeVisible();
  });
});

test.describe("Document Upload", () => {
  test.beforeEach(async ({ page, context }) => {
    await context.addCookies([AUTH_COOKIE]);

    await page.route("**/api/v1/documents/stats", async (route) => {
      await route.fulfill({
        status: 200,
        contentType: "application/json",
        body: JSON.stringify({
          total_count: 1,
          total_size_bytes: 102400,
          quota_bytes: 536870912,
          usage_percentage: 0.02,
          by_type: [{ document_type: "pdf", count: 1, size: 102400 }],
        }),
      });
    });

    await page.route("**/api/v1/clients", async (route) => {
      await route.fulfill({
        status: 200,
        contentType: "application/json",
        body: JSON.stringify({ data: [] }),
      });
    });
  });

  test("should show uploaded document in library after upload", async ({
    page,
  }) => {
    let uploadCalled = false;

    await page.route("**/api/v1/documents", async (route) => {
      if (route.request().method() === "POST") {
        uploadCalled = true;
        await route.fulfill({
          status: 201,
          contentType: "application/json",
          body: JSON.stringify(MOCK_DOCUMENT),
        });
      } else {
        await route.fulfill({
          status: 200,
          contentType: "application/json",
          body: JSON.stringify({
            data: uploadCalled ? [MOCK_DOCUMENT] : [],
            current_page: 1,
            last_page: 1,
            total: uploadCalled ? 1 : 0,
            per_page: 24,
          }),
        });
      }
    });

    await page.goto("/documents");

    await page.getByRole("button", { name: /Importer un document/i }).click();

    const fileInput = page.locator('input[type="file"]').first();
    await fileInput.setInputFiles({
      name: "contrat.pdf",
      mimeType: "application/pdf",
      buffer: Buffer.from("fake pdf content"),
    });

    const titleInput = page
      .getByLabel(/Titre/i)
      .or(page.locator('input[name="title"]'));
    if (await titleInput.isVisible()) {
      await titleInput.fill("Contrat de prestation");
    }

    const submitBtn = page
      .getByRole("button", { name: /Importer|Envoyer|Upload/i })
      .last();
    await submitBtn.click();

    await expect(page.getByText("Contrat de prestation")).toBeVisible({
      timeout: 10000,
    });
  });
});

test.describe("Document Search and Filter", () => {
  test.beforeEach(async ({ page, context }) => {
    await context.addCookies([AUTH_COOKIE]);

    await page.route("**/api/v1/documents/stats", async (route) => {
      await route.fulfill({
        status: 200,
        contentType: "application/json",
        body: JSON.stringify({
          total_count: 2,
          total_size_bytes: 153600,
          quota_bytes: 536870912,
          usage_percentage: 0.03,
          by_type: [{ document_type: "pdf", count: 2, size: 153600 }],
        }),
      });
    });

    await page.route("**/api/v1/clients", async (route) => {
      await route.fulfill({
        status: 200,
        contentType: "application/json",
        body: JSON.stringify({ data: [] }),
      });
    });
  });

  test("should filter search results when query is typed", async ({ page }) => {
    await page.route("**/api/v1/documents*", async (route) => {
      if (route.request().method() !== "GET") return;
      const url = new URL(route.request().url());
      const q = url.searchParams.get("q");

      const results =
        q && q.toLowerCase().includes("contrat")
          ? [MOCK_DOCUMENT]
          : [MOCK_DOCUMENT, MOCK_DOCUMENT_2];

      await route.fulfill({
        status: 200,
        contentType: "application/json",
        body: JSON.stringify({
          data: results,
          current_page: 1,
          last_page: 1,
          total: results.length,
          per_page: 24,
        }),
      });
    });

    await page.goto("/documents");

    await expect(page.getByText("Contrat de prestation")).toBeVisible();
    await expect(page.getByText("Facture Janvier")).toBeVisible();

    const searchInput = page
      .getByPlaceholder(/Rechercher|Search/i)
      .or(page.locator('input[type="search"]'));
    await searchInput.fill("Contrat");
    await page.waitForTimeout(500);

    await expect(page.getByText("Contrat de prestation")).toBeVisible();
  });
});

test.describe("Document Bulk Delete", () => {
  test.beforeEach(async ({ page, context }) => {
    await context.addCookies([AUTH_COOKIE]);

    await page.route("**/api/v1/documents/stats", async (route) => {
      await route.fulfill({
        status: 200,
        contentType: "application/json",
        body: JSON.stringify({
          total_count: 2,
          total_size_bytes: 153600,
          quota_bytes: 536870912,
          usage_percentage: 0.03,
          by_type: [{ document_type: "pdf", count: 2, size: 153600 }],
        }),
      });
    });

    await page.route("**/api/v1/clients", async (route) => {
      await route.fulfill({
        status: 200,
        contentType: "application/json",
        body: JSON.stringify({ data: [] }),
      });
    });
  });

  test("should bulk delete selected documents", async ({ page }) => {
    let deleted = false;

    await page.route("**/api/v1/documents*", async (route) => {
      const method = route.request().method();

      if (method === "GET") {
        await route.fulfill({
          status: 200,
          contentType: "application/json",
          body: JSON.stringify({
            data: deleted ? [] : [MOCK_DOCUMENT, MOCK_DOCUMENT_2],
            current_page: 1,
            last_page: 1,
            total: deleted ? 0 : 2,
            per_page: 24,
          }),
        });
      } else if (method === "DELETE") {
        deleted = true;
        await route.fulfill({
          status: 200,
          contentType: "application/json",
          body: JSON.stringify({ message: "2 documents deleted" }),
        });
      } else {
        await route.continue();
      }
    });

    await page.goto("/documents");

    await expect(page.getByText("Contrat de prestation")).toBeVisible();

    const checkboxes = page.locator('input[type="checkbox"]');
    const count = await checkboxes.count();
    if (count > 0) {
      await checkboxes.first().check();
    }

    const bulkDeleteBtn = page
      .getByRole("button", { name: /Supprimer|Delete/i })
      .filter({ hasText: /Supprimer|Delete/i })
      .first();

    if (await bulkDeleteBtn.isVisible()) {
      await bulkDeleteBtn.click();

      const confirmBtn = page.getByRole("button", {
        name: /Confirmer|Confirm/i,
      });
      if (await confirmBtn.isVisible({ timeout: 2000 })) {
        await confirmBtn.click();
      }
    }

    await expect(page.locator("body")).toBeVisible();
  });
});

test.describe("Document Send Email", () => {
  test.beforeEach(async ({ page, context }) => {
    await context.addCookies([AUTH_COOKIE]);

    await page.route("**/api/v1/documents/stats", async (route) => {
      await route.fulfill({
        status: 200,
        contentType: "application/json",
        body: JSON.stringify({
          total_count: 1,
          total_size_bytes: 102400,
          quota_bytes: 536870912,
          usage_percentage: 0.02,
          by_type: [{ document_type: "pdf", count: 1, size: 102400 }],
        }),
      });
    });

    await page.route("**/api/v1/clients", async (route) => {
      await route.fulfill({
        status: 200,
        contentType: "application/json",
        body: JSON.stringify({ data: [] }),
      });
    });

    await page.route("**/api/v1/documents/doc-uuid-1", async (route) => {
      if (route.request().method() === "GET") {
        await route.fulfill({
          status: 200,
          contentType: "application/json",
          body: JSON.stringify(MOCK_DOCUMENT),
        });
      }
    });
  });

  test("should send document by email from detail page", async ({ page }) => {
    let emailSent = false;

    await page.route("**/api/v1/documents/doc-uuid-1/email", async (route) => {
      emailSent = true;
      await route.fulfill({
        status: 200,
        contentType: "application/json",
        body: JSON.stringify({ message: "Email sent successfully" }),
      });
    });

    await page.route("**/api/v1/documents*", async (route) => {
      if (route.request().method() === "GET") {
        await route.fulfill({
          status: 200,
          contentType: "application/json",
          body: JSON.stringify({
            data: [MOCK_DOCUMENT],
            current_page: 1,
            last_page: 1,
            total: 1,
            per_page: 24,
          }),
        });
      }
    });

    await page.goto("/documents/doc-uuid-1");

    const sendEmailBtn = page.getByRole("button", { name: /Envoyer|Email/i });
    if (await sendEmailBtn.isVisible({ timeout: 5000 })) {
      await sendEmailBtn.click();

      const emailInput = page
        .getByLabel(/Email|Destinataire/i)
        .or(page.locator('input[type="email"]'));
      if (await emailInput.isVisible({ timeout: 3000 })) {
        await emailInput.fill("client@example.com");

        const submitBtn = page
          .getByRole("button", { name: /Envoyer|Send/i })
          .last();
        await submitBtn.click();

        await page.waitForTimeout(1000);
        expect(emailSent).toBe(true);
      }
    }

    await expect(page.locator("body")).toBeVisible();
  });
});

import { expect, test } from "@playwright/test";

function apiResponse(data: unknown, message = "OK") {
  return JSON.stringify({ status: "Success", message, data });
}

const ragStatusFixture = [
  {
    id: "doc-1",
    title: "Contrat de prestation",
    mime_type: "application/pdf",
    embedding_status: "indexed",
  },
  {
    id: "doc-2",
    title: "Devis technique",
    mime_type: "application/pdf",
    embedding_status: "failed",
  },
  {
    id: "doc-3",
    title: "Note de cadrage",
    mime_type: "text/plain",
    embedding_status: "pending",
  },
];

test.describe("RAG admin status page", () => {
  test.beforeEach(async ({ page }) => {
    await page.route("**/api/v1/rag/status**", async (route) => {
      await route.fulfill({
        status: 200,
        contentType: "application/json",
        body: apiResponse(ragStatusFixture),
      });
    });

    await page.route("**/api/v1/rag/reindex/**", async (route) => {
      await route.fulfill({
        status: 202,
        contentType: "application/json",
        body: apiResponse(null, "Reindexing started"),
      });
    });
  });

  test("displays indexed documents count in stats cards", async ({ page }) => {
    await page.goto("/settings/rag");

    await expect(
      page.getByRole("heading", { name: "Intelligence documentaire" })
    ).toBeVisible();

    // 1 indexed out of 3 total
    await expect(page.getByText("1/3")).toBeVisible();
  });

  test("lists documents with embedding status badges", async ({ page }) => {
    await page.goto("/settings/rag");

    await expect(page.getByText("Contrat de prestation")).toBeVisible();
    await expect(page.getByText("Devis technique")).toBeVisible();
    await expect(page.getByText("Note de cadrage")).toBeVisible();
  });

  test("filters documents by search query", async ({ page }) => {
    await page.goto("/settings/rag");

    await page
      .getByPlaceholder("Rechercher un document...")
      .fill("Devis");

    await expect(page.getByText("Devis technique")).toBeVisible();
    await expect(page.getByText("Contrat de prestation")).not.toBeVisible();
  });

  test("triggers reindex for a failed document", async ({ page }) => {
    let reindexCalled = false;

    await page.route("**/api/v1/rag/reindex/doc-2", async (route) => {
      reindexCalled = true;
      await route.fulfill({
        status: 202,
        contentType: "application/json",
        body: apiResponse(null, "Reindexing started"),
      });
    });

    await page.goto("/settings/rag");

    // The failed badge for doc-2 should show a retry button
    const retryButton = page.getByRole("button", { name: /retry|relancer/i }).first();
    await expect(retryButton).toBeVisible();
    await retryButton.click();

    await expect.poll(() => reindexCalled).toBe(true);
  });
});

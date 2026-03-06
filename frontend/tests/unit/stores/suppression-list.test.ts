import { beforeEach, describe, expect, it, vi } from "vitest";
import { useSuppressionListStore } from "@/lib/stores/suppression-list";

vi.mock("@/lib/api", () => ({
  apiClient: {
    get: vi.fn(),
    post: vi.fn(),
    delete: vi.fn(),
  },
}));

import { apiClient } from "@/lib/api";

describe("useSuppressionListStore", () => {
  beforeEach(() => {
    useSuppressionListStore.setState({
      entries: [],
      total: 0,
      page: 1,
      search: "",
      isLoading: false,
      error: null,
    });
    vi.clearAllMocks();
  });

  it("fetches and adds suppression entries", async () => {
    (apiClient.get as any).mockResolvedValueOnce({
      data: {
        data: [{ id: "sup_1", email: "blocked@test.dev", reason: "manual" }],
        total: 1,
        current_page: 1,
      },
    });

    await useSuppressionListStore.getState().fetchEntries();
    expect(useSuppressionListStore.getState().entries).toHaveLength(1);

    (apiClient.post as any).mockResolvedValueOnce({
      data: { id: "sup_2", email: "other@test.dev", reason: "manual" },
    });
    const entry = await useSuppressionListStore
      .getState()
      .addEntry("other@test.dev");
    expect(entry?.id).toBe("sup_2");
  });

  it("removes entries and imports/exports csv", async () => {
    useSuppressionListStore.setState({
      entries: [{ id: "sup_1", email: "blocked@test.dev", reason: "manual" }],
      total: 1,
      page: 1,
      search: "",
      isLoading: false,
      error: null,
    });

    (apiClient.delete as any).mockResolvedValueOnce({});
    await useSuppressionListStore.getState().removeEntry("sup_1");
    expect(useSuppressionListStore.getState().entries).toHaveLength(0);

    const file = new File(["email\nhello@test.dev"], "suppression.csv", {
      type: "text/csv",
    });

    (apiClient.post as any).mockResolvedValueOnce({
      data: { imported: 1, skipped: 0 },
    });
    const imported = await useSuppressionListStore.getState().importCsv(file);
    expect(imported.imported).toBe(1);

    (apiClient.get as any).mockResolvedValueOnce({
      data: new Blob(["email\nhello@test.dev"], { type: "text/csv" }),
      headers: new Headers(),
    });
    const exported = await useSuppressionListStore.getState().exportCsv();
    expect(exported).toBeInstanceOf(Blob);
  });
});

import { beforeEach, describe, expect, it, vi } from "vitest";
import { useProspectImportStore } from "@/lib/stores/prospect-import";

vi.mock("@/lib/api", () => ({
  apiClient: {
    get: vi.fn(),
    post: vi.fn(),
    patch: vi.fn(),
  },
}));

import { apiClient } from "@/lib/api";

describe("useProspectImportStore", () => {
  beforeEach(() => {
    useProspectImportStore.getState().reset();
    vi.clearAllMocks();
  });

  it("uploads a file and stores session metadata", async () => {
    (apiClient.post as any).mockResolvedValueOnce({
      data: {
        session: { id: "imp_1", status: "pending" },
        column_list: ["Nom", "Email"],
        preview_rows: [{ Nom: "Acme", Email: "acme@example.com" }],
        detected_mapping: { Nom: "name", Email: "email" },
      },
    });

    const file = new File(["x"], "prospects.csv", { type: "text/csv" });
    await useProspectImportStore.getState().uploadFile(file);

    const state = useProspectImportStore.getState();
    expect(state.session?.id).toBe("imp_1");
    expect(state.columnList).toEqual(["Nom", "Email"]);
  });

  it("updates mapping and options", async () => {
    useProspectImportStore.setState({
      session: {
        id: "imp_1",
        status: "pending",
        total_rows: 1,
        processed_rows: 0,
        success_rows: 0,
        error_rows: 0,
      },
      columnMapping: { Nom: "name" },
    });

    (apiClient.patch as any).mockResolvedValue({ data: {} });

    await useProspectImportStore.getState().updateMapping({ Nom: "name" });
    await useProspectImportStore.getState().updateOptions({
      duplicate_strategy: "update",
      default_status: "lead",
      default_tags: ["wedding"],
    });

    const state = useProspectImportStore.getState();
    expect(state.options.duplicate_strategy).toBe("update");
    expect(state.defaultTags).toEqual(["wedding"]);
  });
});

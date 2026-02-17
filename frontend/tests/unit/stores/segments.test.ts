import { describe, it, expect, beforeEach, vi } from "vitest";
import { useSegmentStore } from "@/lib/stores/segments";

vi.mock("@/lib/api", () => ({
  apiClient: {
    get: vi.fn(),
    post: vi.fn(),
    put: vi.fn(),
    delete: vi.fn(),
  },
}));

import { apiClient } from "@/lib/api";

const baseSegment = {
  id: "seg_1",
  name: "VIP Clients",
  description: "High value clients",
  filters: {
    group_boolean: "and",
    criteria_boolean: "or",
    groups: [
      {
        criteria: [{ type: "revenue", operator: ">", value: 1000 }],
      },
    ],
  },
  contact_count: 5,
};

describe("useSegmentStore", () => {
  beforeEach(() => {
    useSegmentStore.setState({
      segments: [],
      currentSegment: null,
      pagination: null,
      preview: null,
      isLoading: false,
      error: null,
    });
    vi.clearAllMocks();
  });

  it("fetches segments with pagination", async () => {
    (apiClient.get as any).mockResolvedValue({
      data: {
        data: [baseSegment],
        current_page: 1,
        last_page: 1,
        total: 1,
        per_page: 15,
      },
    });

    await useSegmentStore.getState().fetchSegments();

    const state = useSegmentStore.getState();
    expect(state.segments).toHaveLength(1);
    expect(state.pagination?.total).toBe(1);
  });

  it("creates updates previews and deletes segment", async () => {
    (apiClient.post as any).mockResolvedValueOnce({ data: baseSegment });

    const created = await useSegmentStore.getState().createSegment({
      name: "VIP Clients",
      filters: baseSegment.filters,
    });

    expect(created?.id).toBe("seg_1");

    (apiClient.put as any).mockResolvedValueOnce({
      data: { ...baseSegment, name: "Warm Leads" },
    });

    const updated = await useSegmentStore
      .getState()
      .updateSegment("seg_1", { name: "Warm Leads" });

    expect(updated?.name).toBe("Warm Leads");

    (apiClient.get as any).mockResolvedValueOnce({
      data: {
        segment_id: "seg_1",
        total_matching: 2,
        cached_contact_count: 2,
        contacts: {
          data: [
            {
              id: "c1",
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
      },
    });

    const preview = await useSegmentStore.getState().previewSegment("seg_1");

    expect(preview?.total_matching).toBe(2);
    expect(useSegmentStore.getState().preview?.contacts.data).toHaveLength(1);

    (apiClient.delete as any).mockResolvedValue({});

    await useSegmentStore.getState().deleteSegment("seg_1");
    expect(useSegmentStore.getState().segments).toEqual([]);
  });

  it("keeps unrelated current segment on update and delete", async () => {
    useSegmentStore.setState({
      segments: [
        baseSegment as any,
        { ...baseSegment, id: "seg_2", name: "Other segment" } as any,
      ],
      currentSegment: {
        ...baseSegment,
        id: "seg_2",
        name: "Other segment",
      } as any,
    });

    (apiClient.put as any).mockResolvedValueOnce({
      data: { ...baseSegment, id: "seg_1", name: "VIP Updated" },
    });

    const updated = await useSegmentStore
      .getState()
      .updateSegment("seg_1", { name: "VIP Updated" });

    expect(updated?.name).toBe("VIP Updated");
    expect(useSegmentStore.getState().currentSegment?.id).toBe("seg_2");

    (apiClient.delete as any).mockResolvedValueOnce({});
    await useSegmentStore.getState().deleteSegment("seg_1");

    const state = useSegmentStore.getState();
    expect(state.currentSegment?.id).toBe("seg_2");
    expect(state.segments.map((segment) => segment.id)).toEqual(["seg_2"]);
  });

  it("records API failures", async () => {
    (apiClient.get as any).mockRejectedValueOnce(new Error("list failed"));
    await useSegmentStore.getState().fetchSegments();
    expect(useSegmentStore.getState().error).toBe("list failed");

    (apiClient.post as any).mockRejectedValueOnce(new Error("create failed"));
    await expect(
      useSegmentStore.getState().createSegment({
        name: "x",
        filters: baseSegment.filters,
      })
    ).rejects.toThrow("create failed");

    (apiClient.get as any).mockRejectedValueOnce(new Error("preview failed"));
    await expect(
      useSegmentStore.getState().previewSegment("seg_1")
    ).rejects.toThrow("preview failed");

    (apiClient.get as any).mockRejectedValueOnce(new Error("fetch failed"));
    await expect(
      useSegmentStore.getState().fetchSegment("seg_1")
    ).rejects.toThrow("fetch failed");

    (apiClient.put as any).mockRejectedValueOnce(new Error("update failed"));
    await expect(
      useSegmentStore.getState().updateSegment("seg_1", { name: "x" })
    ).rejects.toThrow("update failed");

    (apiClient.delete as any).mockRejectedValueOnce(new Error("delete failed"));
    await expect(
      useSegmentStore.getState().deleteSegment("seg_1")
    ).rejects.toThrow("delete failed");
  });
});

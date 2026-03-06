import { beforeEach, describe, expect, it, vi } from "vitest";
import { useDripSequencesStore } from "@/lib/stores/drip-sequences";

vi.mock("@/lib/api", () => ({
  apiClient: {
    get: vi.fn(),
    post: vi.fn(),
    put: vi.fn(),
    delete: vi.fn(),
    patch: vi.fn(),
  },
}));

import { apiClient } from "@/lib/api";

describe("useDripSequencesStore", () => {
  beforeEach(() => {
    useDripSequencesStore.setState({
      sequences: [],
      currentSequence: null,
      enrollments: [],
      isLoading: false,
      error: null,
    });
    vi.clearAllMocks();
  });

  it("fetches and creates drip sequences", async () => {
    (apiClient.get as any).mockResolvedValueOnce({
      data: [
        {
          id: "seq_1",
          name: "Welcome",
          trigger_event: "manual",
          status: "active",
          steps: [],
          enrollments: [],
        },
      ],
    });

    await useDripSequencesStore.getState().fetchSequences();
    expect(useDripSequencesStore.getState().sequences).toHaveLength(1);

    (apiClient.post as any).mockResolvedValueOnce({
      data: {
        id: "seq_2",
        name: "Follow up",
        trigger_event: "manual",
        status: "active",
        steps: [],
        enrollments: [],
      },
    });

    const created = await useDripSequencesStore
      .getState()
      .createSequence({ name: "Follow up" });

    expect(created?.id).toBe("seq_2");
  });

  it("updates deletes and drives enrollment actions", async () => {
    useDripSequencesStore.setState({
      sequences: [
        {
          id: "seq_1",
          name: "Welcome",
          trigger_event: "manual",
          status: "active",
          steps: [],
          enrollments: [],
        },
      ],
      currentSequence: null,
      enrollments: [],
      isLoading: false,
      error: null,
    });

    (apiClient.put as any).mockResolvedValueOnce({
      data: {
        id: "seq_1",
        name: "Welcome v2",
        trigger_event: "manual",
        status: "paused",
        steps: [],
        enrollments: [],
      },
    });
    const updated = await useDripSequencesStore
      .getState()
      .updateSequence("seq_1", { name: "Welcome v2" });
    expect(updated?.name).toBe("Welcome v2");

    (apiClient.post as any)
      .mockResolvedValueOnce({ data: { id: "enr_1", status: "active" } })
      .mockResolvedValueOnce({ data: { enrolled: 3 } });
    await useDripSequencesStore.getState().enrollContact("seq_1", "ct_1");
    const enrolled = await useDripSequencesStore
      .getState()
      .enrollSegment("seq_1", "seg_1");
    expect(enrolled).toBe(3);

    (apiClient.patch as any)
      .mockResolvedValueOnce({ data: { id: "enr_1", status: "paused" } })
      .mockResolvedValueOnce({ data: { id: "enr_1", status: "active" } })
      .mockResolvedValueOnce({ data: { id: "enr_1", status: "cancelled" } });

    await useDripSequencesStore.getState().pauseEnrollment("enr_1");
    await useDripSequencesStore.getState().resumeEnrollment("enr_1");
    await useDripSequencesStore.getState().cancelEnrollment("enr_1");

    (apiClient.delete as any).mockResolvedValueOnce({});
    await useDripSequencesStore.getState().deleteSequence("seq_1");
    expect(useDripSequencesStore.getState().sequences).toHaveLength(0);
  });
});

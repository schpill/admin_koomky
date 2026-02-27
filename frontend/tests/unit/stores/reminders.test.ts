import { beforeEach, describe, expect, it, vi } from "vitest";
import { useReminderStore } from "@/lib/stores/reminders";

vi.mock("@/lib/api", () => ({
  apiClient: {
    get: vi.fn(),
    post: vi.fn(),
    patch: vi.fn(),
    delete: vi.fn(),
  },
}));

import { apiClient } from "@/lib/api";

describe("useReminderStore", () => {
  beforeEach(() => {
    useReminderStore.setState({
      sequences: [],
      selectedSequence: null,
      invoiceReminder: null,
      isLoading: false,
      error: null,
    } as any);
    vi.clearAllMocks();
  });

  it("fetches and creates sequences", async () => {
    (apiClient.get as any).mockResolvedValue({
      data: [{ id: "s1", name: "A", steps: [] }],
    });
    await useReminderStore.getState().fetchSequences();
    expect(useReminderStore.getState().sequences).toHaveLength(1);

    (apiClient.post as any).mockResolvedValue({
      data: { id: "s2", name: "B", steps: [] },
    });
    const created = await useReminderStore
      .getState()
      .createSequence({ name: "B", steps: [] } as any);
    expect(created.id).toBe("s2");
    expect(useReminderStore.getState().sequences[0].id).toBe("s2");
  });

  it("handles invoice reminder lifecycle", async () => {
    (apiClient.get as any).mockResolvedValue({ data: null });
    await useReminderStore.getState().fetchInvoiceReminder("inv_1");
    expect(useReminderStore.getState().invoiceReminder).toBeNull();

    (apiClient.post as any).mockResolvedValueOnce({
      data: { id: "r1", is_paused: false },
    });
    await useReminderStore.getState().attachSequence("inv_1", "seq_1");
    expect(useReminderStore.getState().invoiceReminder?.id).toBe("r1");

    (apiClient.delete as any).mockResolvedValue({});
    await useReminderStore.getState().cancelReminder("inv_1");
    expect(useReminderStore.getState().invoiceReminder).toBeNull();
  });
});

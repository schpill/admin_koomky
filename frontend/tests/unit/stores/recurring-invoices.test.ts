import { beforeEach, describe, expect, it, vi } from "vitest";
import { useRecurringInvoiceStore } from "@/lib/stores/recurring-invoices";

vi.mock("@/lib/api", () => ({
  apiClient: {
    get: vi.fn(),
    post: vi.fn(),
    put: vi.fn(),
    delete: vi.fn(),
  },
}));

import { apiClient } from "@/lib/api";

describe("useRecurringInvoiceStore", () => {
  const baseProfile = {
    id: "rip_1",
    client_id: "cli_1",
    name: "Monthly retainer",
    frequency: "monthly",
    start_date: "2026-02-01",
    next_due_date: "2026-02-15",
    line_items: [
      { description: "Retainer", quantity: 1, unit_price: 1200, vat_rate: 20 },
    ],
    payment_terms_days: 30,
    status: "active",
    occurrences_generated: 0,
    auto_send: true,
    currency: "EUR",
  };

  beforeEach(() => {
    useRecurringInvoiceStore.setState({
      profiles: [],
      currentProfile: null,
      pagination: null,
      isLoading: false,
      error: null,
    });
    vi.clearAllMocks();
  });

  it("fetches recurring profiles", async () => {
    (apiClient.get as any).mockResolvedValue({
      data: {
        data: [baseProfile],
        current_page: 1,
        last_page: 1,
        per_page: 15,
        total: 1,
      },
    });

    await useRecurringInvoiceStore.getState().fetchProfiles();

    const state = useRecurringInvoiceStore.getState();
    expect(state.profiles).toHaveLength(1);
    expect(state.pagination?.total).toBe(1);
  });

  it("uses fallback values when recurring profile payload is empty", async () => {
    (apiClient.get as any).mockResolvedValue({
      data: null,
    });

    await useRecurringInvoiceStore.getState().fetchProfiles();

    const state = useRecurringInvoiceStore.getState();
    expect(state.profiles).toEqual([]);
    expect(state.pagination).toEqual({
      current_page: 1,
      last_page: 1,
      total: 0,
      per_page: 15,
    });
  });

  it("creates updates pauses resumes cancels and deletes profile", async () => {
    (apiClient.post as any).mockResolvedValueOnce({ data: baseProfile });

    const created = await useRecurringInvoiceStore.getState().createProfile({
      client_id: "cli_1",
      name: "Monthly retainer",
      frequency: "monthly",
      start_date: "2026-02-01",
      line_items: [
        {
          description: "Retainer",
          quantity: 1,
          unit_price: 1200,
          vat_rate: 20,
        },
      ],
    });

    expect(created?.id).toBe("rip_1");

    (apiClient.put as any).mockResolvedValueOnce({
      data: { ...baseProfile, name: "Updated profile" },
    });

    const updated = await useRecurringInvoiceStore
      .getState()
      .updateProfile("rip_1", {
        client_id: "cli_1",
        name: "Updated profile",
        frequency: "monthly",
        start_date: "2026-02-01",
        line_items: baseProfile.line_items,
      });

    expect(updated?.name).toBe("Updated profile");

    (apiClient.post as any).mockResolvedValueOnce({
      data: { ...baseProfile, status: "paused" },
    });
    const paused = await useRecurringInvoiceStore
      .getState()
      .pauseProfile("rip_1");
    expect(paused?.status).toBe("paused");

    (apiClient.post as any).mockResolvedValueOnce({
      data: { ...baseProfile, status: "active" },
    });
    const resumed = await useRecurringInvoiceStore
      .getState()
      .resumeProfile("rip_1");
    expect(resumed?.status).toBe("active");

    (apiClient.post as any).mockResolvedValueOnce({
      data: { ...baseProfile, status: "cancelled" },
    });
    const cancelled = await useRecurringInvoiceStore
      .getState()
      .cancelProfile("rip_1");
    expect(cancelled?.status).toBe("cancelled");

    (apiClient.delete as any).mockResolvedValueOnce({});
    await useRecurringInvoiceStore.getState().deleteProfile("rip_1");

    expect(useRecurringInvoiceStore.getState().profiles).toEqual([]);
  });

  it("fetches a single profile and keeps unrelated current profile on actions", async () => {
    useRecurringInvoiceStore.setState({
      profiles: [
        { ...baseProfile, id: "rip_1", status: "active" } as any,
        {
          ...baseProfile,
          id: "rip_2",
          name: "Other profile",
          status: "active",
        } as any,
      ],
      currentProfile: {
        ...baseProfile,
        id: "rip_2",
        name: "Other profile",
        status: "active",
      } as any,
    });

    (apiClient.get as any).mockResolvedValueOnce({
      data: { ...baseProfile, id: "rip_1", status: "active" },
    });

    const fetched = await useRecurringInvoiceStore
      .getState()
      .fetchProfile("rip_1");
    expect(fetched?.id).toBe("rip_1");
    useRecurringInvoiceStore.setState({
      currentProfile: {
        ...baseProfile,
        id: "rip_2",
        name: "Other profile",
        status: "active",
      } as any,
    });

    (apiClient.put as any).mockResolvedValueOnce({
      data: { ...baseProfile, id: "rip_1", name: "Updated profile" },
    });
    await useRecurringInvoiceStore.getState().updateProfile("rip_1", {
      client_id: "cli_1",
      name: "Updated profile",
      frequency: "monthly",
      start_date: "2026-02-01",
      line_items: baseProfile.line_items,
    });
    expect(useRecurringInvoiceStore.getState().currentProfile?.id).toBe(
      "rip_2"
    );

    (apiClient.post as any).mockResolvedValueOnce({
      data: { ...baseProfile, id: "rip_1", status: "paused" },
    });
    await useRecurringInvoiceStore.getState().pauseProfile("rip_1");
    expect(useRecurringInvoiceStore.getState().currentProfile?.id).toBe(
      "rip_2"
    );

    (apiClient.post as any).mockResolvedValueOnce({
      data: { ...baseProfile, id: "rip_1", status: "active" },
    });
    await useRecurringInvoiceStore.getState().resumeProfile("rip_1");
    expect(useRecurringInvoiceStore.getState().currentProfile?.id).toBe(
      "rip_2"
    );

    (apiClient.post as any).mockResolvedValueOnce({
      data: { ...baseProfile, id: "rip_1", status: "cancelled" },
    });
    await useRecurringInvoiceStore.getState().cancelProfile("rip_1");
    expect(useRecurringInvoiceStore.getState().currentProfile?.id).toBe(
      "rip_2"
    );

    (apiClient.delete as any).mockResolvedValueOnce({});
    await useRecurringInvoiceStore.getState().deleteProfile("rip_1");

    const state = useRecurringInvoiceStore.getState();
    expect(state.currentProfile?.id).toBe("rip_2");
    expect(state.profiles.map((profile) => profile.id)).toEqual(["rip_2"]);
  });

  it("records list errors and throws action errors", async () => {
    (apiClient.get as any).mockRejectedValueOnce(new Error("list failed"));
    await useRecurringInvoiceStore.getState().fetchProfiles();
    expect(useRecurringInvoiceStore.getState().error).toBe("list failed");

    (apiClient.get as any).mockRejectedValueOnce(new Error("fetch failed"));
    await expect(
      useRecurringInvoiceStore.getState().fetchProfile("rip_404")
    ).rejects.toThrow("fetch failed");

    (apiClient.post as any).mockRejectedValueOnce(new Error("create failed"));
    await expect(
      useRecurringInvoiceStore.getState().createProfile({
        client_id: "cli_1",
        name: "x",
        frequency: "monthly",
        start_date: "2026-02-01",
        line_items: [
          {
            description: "Retainer",
            quantity: 1,
            unit_price: 1200,
            vat_rate: 20,
          },
        ],
      })
    ).rejects.toThrow("create failed");

    (apiClient.put as any).mockRejectedValueOnce(new Error("update failed"));
    await expect(
      useRecurringInvoiceStore.getState().updateProfile("rip_1", {
        client_id: "cli_1",
        name: "x",
        frequency: "monthly",
        start_date: "2026-02-01",
        line_items: baseProfile.line_items,
      })
    ).rejects.toThrow("update failed");

    (apiClient.delete as any).mockRejectedValueOnce(new Error("delete failed"));
    await expect(
      useRecurringInvoiceStore.getState().deleteProfile("rip_1")
    ).rejects.toThrow("delete failed");

    (apiClient.post as any).mockRejectedValueOnce(new Error("pause failed"));
    await expect(
      useRecurringInvoiceStore.getState().pauseProfile("rip_1")
    ).rejects.toThrow("pause failed");

    (apiClient.post as any).mockRejectedValueOnce(new Error("resume failed"));
    await expect(
      useRecurringInvoiceStore.getState().resumeProfile("rip_1")
    ).rejects.toThrow("resume failed");

    (apiClient.post as any).mockRejectedValueOnce(new Error("cancel failed"));
    await expect(
      useRecurringInvoiceStore.getState().cancelProfile("rip_1")
    ).rejects.toThrow("cancel failed");
  });
});

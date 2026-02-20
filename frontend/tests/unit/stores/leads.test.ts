import { beforeEach, describe, expect, it, vi } from "vitest";
import { useLeadStore } from "@/lib/stores/leads";

vi.mock("@/lib/api", () => ({
  apiClient: {
    get: vi.fn(),
    post: vi.fn(),
    put: vi.fn(),
    patch: vi.fn(),
    delete: vi.fn(),
  },
}));

import { apiClient } from "@/lib/api";

const baseLead = {
  id: "lead_1",
  user_id: "user_1",
  company_name: "Acme Corp",
  first_name: "Alice",
  last_name: "Doe",
  full_name: "Alice Doe",
  email: "alice@acme.com",
  phone: "+33 6 00 00 00 00",
  source: "referral" as const,
  status: "new" as const,
  estimated_value: 5000,
  currency: "EUR",
  probability: 60,
  expected_close_date: "2026-06-30",
  notes: "High priority prospect",
  lost_reason: null,
  converted_at: null,
  can_convert: false,
  is_terminal: false,
  created_at: "2026-02-01T00:00:00Z",
  updated_at: "2026-02-01T00:00:00Z",
};

const baseActivity = {
  id: "act_1",
  type: "call" as const,
  content: "Initial call with prospect",
  scheduled_at: "2026-02-10T10:00:00Z",
  completed_at: null,
  created_at: "2026-02-01T00:00:00Z",
};

describe("useLeadStore", () => {
  beforeEach(() => {
    useLeadStore.setState({
      leads: [],
      currentLead: null,
      pipeline: null,
      analytics: null,
      pagination: null,
      isLoading: false,
      error: null,
    });
    vi.clearAllMocks();
  });

  describe("fetchLeads", () => {
    it("fetches leads and updates pagination", async () => {
      (apiClient.get as ReturnType<typeof vi.fn>).mockResolvedValue({
        data: {
          data: [baseLead],
          current_page: 1,
          last_page: 2,
          total: 15,
        },
      });

      await useLeadStore.getState().fetchLeads();

      const state = useLeadStore.getState();
      expect(state.leads).toHaveLength(1);
      expect(state.leads[0].id).toBe("lead_1");
      expect(state.pagination?.total).toBe(15);
      expect(state.pagination?.current_page).toBe(1);
      expect(state.pagination?.last_page).toBe(2);
      expect(state.isLoading).toBe(false);
    });

    it("handles empty response payload with defaults", async () => {
      (apiClient.get as ReturnType<typeof vi.fn>).mockResolvedValue({
        data: {},
      });

      await useLeadStore.getState().fetchLeads();

      const state = useLeadStore.getState();
      expect(state.leads).toEqual([]);
      expect(state.pagination?.total).toBe(0);
      expect(state.pagination?.current_page).toBe(1);
      expect(state.pagination?.last_page).toBe(1);
    });

    it("applies filters as params when provided", async () => {
      (apiClient.get as ReturnType<typeof vi.fn>).mockResolvedValue({
        data: { data: [], current_page: 1, last_page: 1, total: 0 },
      });

      await useLeadStore.getState().fetchLeads({ status: "qualified", source: "website" });

      expect(apiClient.get).toHaveBeenCalledWith("/leads", {
        params: { status: "qualified", source: "website" },
      });
    });

    it("records error on fetch failure", async () => {
      (apiClient.get as ReturnType<typeof vi.fn>).mockRejectedValue(
        new Error("Network error")
      );

      await useLeadStore.getState().fetchLeads();

      expect(useLeadStore.getState().error).toBe("Network error");
      expect(useLeadStore.getState().isLoading).toBe(false);
    });
  });

  describe("fetchLead", () => {
    it("fetches single lead and sets currentLead", async () => {
      (apiClient.get as ReturnType<typeof vi.fn>).mockResolvedValue({
        data: baseLead,
      });

      useLeadStore.setState({ leads: [baseLead] });

      const lead = await useLeadStore.getState().fetchLead("lead_1");

      expect(lead?.id).toBe("lead_1");
      expect(useLeadStore.getState().currentLead?.id).toBe("lead_1");
      expect(useLeadStore.getState().isLoading).toBe(false);
    });

    it("updates matching lead in list on fetch", async () => {
      const updatedLead = { ...baseLead, notes: "Updated notes" };
      (apiClient.get as ReturnType<typeof vi.fn>).mockResolvedValue({
        data: updatedLead,
      });

      useLeadStore.setState({ leads: [baseLead] });

      await useLeadStore.getState().fetchLead("lead_1");

      expect(useLeadStore.getState().leads[0].notes).toBe("Updated notes");
    });

    it("throws and sets error on fetch failure", async () => {
      (apiClient.get as ReturnType<typeof vi.fn>).mockRejectedValue(
        new Error("Lead not found")
      );

      await expect(useLeadStore.getState().fetchLead("lead_999")).rejects.toThrow(
        "Lead not found"
      );
      expect(useLeadStore.getState().error).toBe("Lead not found");
    });
  });

  describe("createLead", () => {
    it("creates a lead and prepends to list", async () => {
      const existingLead = { ...baseLead, id: "lead_0" };
      useLeadStore.setState({ leads: [existingLead] });

      (apiClient.post as ReturnType<typeof vi.fn>).mockResolvedValue({
        data: baseLead,
      });

      const created = await useLeadStore.getState().createLead({
        first_name: "Alice",
        last_name: "Doe",
        email: "alice@acme.com",
        source: "referral",
      });

      expect(created?.id).toBe("lead_1");
      expect(useLeadStore.getState().leads[0].id).toBe("lead_1");
      expect(useLeadStore.getState().leads).toHaveLength(2);
    });

    it("throws on validation error", async () => {
      (apiClient.post as ReturnType<typeof vi.fn>).mockRejectedValue(
        new Error("Validation failed")
      );

      await expect(
        useLeadStore.getState().createLead({ first_name: "" })
      ).rejects.toThrow("Validation failed");

      expect(useLeadStore.getState().error).toBe("Validation failed");
    });
  });

  describe("updateLead", () => {
    it("updates lead in list and currentLead when matching", async () => {
      const updatedLead = { ...baseLead, notes: "Updated via test" };
      useLeadStore.setState({
        leads: [baseLead],
        currentLead: baseLead,
      });

      (apiClient.put as ReturnType<typeof vi.fn>).mockResolvedValue({
        data: updatedLead,
      });

      const result = await useLeadStore.getState().updateLead("lead_1", {
        notes: "Updated via test",
      });

      expect(result?.notes).toBe("Updated via test");
      expect(useLeadStore.getState().leads[0].notes).toBe("Updated via test");
      expect(useLeadStore.getState().currentLead?.notes).toBe("Updated via test");
    });

    it("does not update currentLead when id does not match", async () => {
      const otherLead = { ...baseLead, id: "lead_2" };
      const updatedLead = { ...baseLead, notes: "Changed" };
      useLeadStore.setState({
        leads: [baseLead, otherLead],
        currentLead: otherLead,
      });

      (apiClient.put as ReturnType<typeof vi.fn>).mockResolvedValue({
        data: updatedLead,
      });

      await useLeadStore.getState().updateLead("lead_1", { notes: "Changed" });

      expect(useLeadStore.getState().currentLead?.id).toBe("lead_2");
    });

    it("throws on update failure", async () => {
      (apiClient.put as ReturnType<typeof vi.fn>).mockRejectedValue(
        new Error("Update failed")
      );

      await expect(
        useLeadStore.getState().updateLead("lead_1", { notes: "x" })
      ).rejects.toThrow("Update failed");
    });
  });

  describe("deleteLead", () => {
    it("removes lead from list", async () => {
      const otherLead = { ...baseLead, id: "lead_2" };
      useLeadStore.setState({ leads: [baseLead, otherLead] });

      (apiClient.delete as ReturnType<typeof vi.fn>).mockResolvedValue({});

      await useLeadStore.getState().deleteLead("lead_1");

      expect(useLeadStore.getState().leads).toHaveLength(1);
      expect(useLeadStore.getState().leads[0].id).toBe("lead_2");
    });

    it("clears currentLead when deleted lead matches", async () => {
      useLeadStore.setState({ leads: [baseLead], currentLead: baseLead });

      (apiClient.delete as ReturnType<typeof vi.fn>).mockResolvedValue({});

      await useLeadStore.getState().deleteLead("lead_1");

      expect(useLeadStore.getState().currentLead).toBeNull();
    });

    it("preserves currentLead when a different lead is deleted", async () => {
      const otherLead = { ...baseLead, id: "lead_2" };
      useLeadStore.setState({
        leads: [baseLead, otherLead],
        currentLead: otherLead,
      });

      (apiClient.delete as ReturnType<typeof vi.fn>).mockResolvedValue({});

      await useLeadStore.getState().deleteLead("lead_1");

      expect(useLeadStore.getState().currentLead?.id).toBe("lead_2");
    });

    it("throws on delete failure", async () => {
      (apiClient.delete as ReturnType<typeof vi.fn>).mockRejectedValue(
        new Error("Delete failed")
      );

      await expect(
        useLeadStore.getState().deleteLead("lead_1")
      ).rejects.toThrow("Delete failed");
    });
  });

  describe("fetchPipeline", () => {
    it("fetches pipeline data and groups leads by status", async () => {
      const pipelineData = {
        columns: {
          new: [baseLead],
          contacted: [],
          qualified: [],
          proposal_sent: [],
          negotiating: [],
        },
        column_stats: {
          new: { count: 1, total_value: 5000 },
          contacted: { count: 0, total_value: 0 },
        },
        total_pipeline_value: 5000,
      };

      (apiClient.get as ReturnType<typeof vi.fn>).mockResolvedValue({
        data: pipelineData,
      });

      await useLeadStore.getState().fetchPipeline();

      const state = useLeadStore.getState();
      expect(state.pipeline).not.toBeNull();
      expect(state.pipeline?.columns.new).toHaveLength(1);
      expect(state.pipeline?.total_pipeline_value).toBe(5000);
      expect(state.isLoading).toBe(false);
    });

    it("includes totals per column in column_stats", async () => {
      const pipelineData = {
        columns: { new: [baseLead, { ...baseLead, id: "lead_2" }] },
        column_stats: { new: { count: 2, total_value: 10000 } },
        total_pipeline_value: 10000,
      };

      (apiClient.get as ReturnType<typeof vi.fn>).mockResolvedValue({
        data: pipelineData,
      });

      await useLeadStore.getState().fetchPipeline();

      expect(useLeadStore.getState().pipeline?.column_stats.new.count).toBe(2);
      expect(useLeadStore.getState().pipeline?.column_stats.new.total_value).toBe(10000);
    });

    it("records error when pipeline fetch fails", async () => {
      (apiClient.get as ReturnType<typeof vi.fn>).mockRejectedValue(
        new Error("Pipeline unavailable")
      );

      await useLeadStore.getState().fetchPipeline();

      expect(useLeadStore.getState().error).toBe("Pipeline unavailable");
    });
  });

  describe("fetchAnalytics", () => {
    it("returns win rate and average deal value", async () => {
      const analyticsData = {
        total_pipeline_value: 50000,
        leads_by_status: { new: 3, qualified: 2, won: 1 },
        win_rate: 16.67,
        average_deal_value: 8333,
        average_time_to_close: 45,
        pipeline_by_source: [
          { source: "referral", count: 3, total_value: 30000 },
        ],
      };

      (apiClient.get as ReturnType<typeof vi.fn>).mockResolvedValue({
        data: analyticsData,
      });

      await useLeadStore.getState().fetchAnalytics();

      const state = useLeadStore.getState();
      expect(state.analytics?.win_rate).toBe(16.67);
      expect(state.analytics?.average_deal_value).toBe(8333);
      expect(state.analytics?.total_pipeline_value).toBe(50000);
      expect(state.isLoading).toBe(false);
    });

    it("passes params to the analytics endpoint", async () => {
      (apiClient.get as ReturnType<typeof vi.fn>).mockResolvedValue({
        data: {
          total_pipeline_value: 0,
          leads_by_status: {},
          win_rate: 0,
          average_deal_value: 0,
          average_time_to_close: 0,
          pipeline_by_source: [],
        },
      });

      await useLeadStore.getState().fetchAnalytics({ period: "last_30_days" });

      expect(apiClient.get).toHaveBeenCalledWith("/leads/analytics", {
        params: { period: "last_30_days" },
      });
    });

    it("records error when analytics fetch fails", async () => {
      (apiClient.get as ReturnType<typeof vi.fn>).mockRejectedValue(
        new Error("Analytics error")
      );

      await useLeadStore.getState().fetchAnalytics();

      expect(useLeadStore.getState().error).toBe("Analytics error");
    });
  });

  describe("updateStatus", () => {
    it("updates lead status successfully", async () => {
      const qualifiedLead = { ...baseLead, status: "qualified" as const };
      useLeadStore.setState({ leads: [baseLead], currentLead: baseLead });

      (apiClient.patch as ReturnType<typeof vi.fn>).mockResolvedValue({
        data: qualifiedLead,
      });

      const result = await useLeadStore.getState().updateStatus("lead_1", "qualified");

      expect(result?.status).toBe("qualified");
      expect(useLeadStore.getState().leads[0].status).toBe("qualified");
      expect(useLeadStore.getState().currentLead?.status).toBe("qualified");
    });

    it("includes lost_reason in payload when status is lost", async () => {
      const lostLead = {
        ...baseLead,
        status: "lost" as const,
        lost_reason: "Budget constraints",
      };
      useLeadStore.setState({ leads: [baseLead] });

      (apiClient.patch as ReturnType<typeof vi.fn>).mockResolvedValue({
        data: lostLead,
      });

      await useLeadStore.getState().updateStatus(
        "lead_1",
        "lost",
        "Budget constraints"
      );

      expect(apiClient.patch).toHaveBeenCalledWith("/leads/lead_1/status", {
        status: "lost",
        lost_reason: "Budget constraints",
      });
      expect(useLeadStore.getState().leads[0].lost_reason).toBe(
        "Budget constraints"
      );
    });

    it("does not include lost_reason when not provided", async () => {
      const contactedLead = { ...baseLead, status: "contacted" as const };
      useLeadStore.setState({ leads: [baseLead] });

      (apiClient.patch as ReturnType<typeof vi.fn>).mockResolvedValue({
        data: contactedLead,
      });

      await useLeadStore.getState().updateStatus("lead_1", "contacted");

      expect(apiClient.patch).toHaveBeenCalledWith("/leads/lead_1/status", {
        status: "contacted",
      });
    });

    it("throws on status update failure", async () => {
      (apiClient.patch as ReturnType<typeof vi.fn>).mockRejectedValue(
        new Error("Status update failed")
      );

      await expect(
        useLeadStore.getState().updateStatus("lead_1", "won")
      ).rejects.toThrow("Status update failed");
    });
  });

  describe("updatePosition", () => {
    it("calls patch with position payload", async () => {
      (apiClient.patch as ReturnType<typeof vi.fn>).mockResolvedValue({});

      await useLeadStore.getState().updatePosition("lead_1", 3);

      expect(apiClient.patch).toHaveBeenCalledWith("/leads/lead_1/position", {
        position: 3,
      });
    });

    it("throws and records error on position update failure", async () => {
      (apiClient.patch as ReturnType<typeof vi.fn>).mockRejectedValue(
        new Error("Position error")
      );

      await expect(
        useLeadStore.getState().updatePosition("lead_1", 0)
      ).rejects.toThrow("Position error");

      expect(useLeadStore.getState().error).toBe("Position error");
    });
  });

  describe("convertToClient", () => {
    it("converts lead to client and updates state", async () => {
      const wonLead = {
        ...baseLead,
        status: "won" as const,
        converted_at: "2026-02-20T12:00:00Z",
        can_convert: false,
        is_terminal: true,
      };
      const convertResult = {
        client: { id: "client_1", name: "Acme Corp" },
        lead: wonLead,
      };

      useLeadStore.setState({ leads: [baseLead], currentLead: baseLead });

      (apiClient.post as ReturnType<typeof vi.fn>).mockResolvedValue({
        data: convertResult,
      });

      const result = await useLeadStore.getState().convertToClient("lead_1");

      expect(result?.client.id).toBe("client_1");
      expect(result?.lead.converted_at).toBe("2026-02-20T12:00:00Z");
      expect(useLeadStore.getState().leads[0].status).toBe("won");
      expect(useLeadStore.getState().currentLead?.converted_at).toBe(
        "2026-02-20T12:00:00Z"
      );
    });

    it("passes overrides to convert endpoint", async () => {
      const wonLead = { ...baseLead, status: "won" as const };
      (apiClient.post as ReturnType<typeof vi.fn>).mockResolvedValue({
        data: { client: { id: "client_2", name: "Custom Name" }, lead: wonLead },
      });

      await useLeadStore.getState().convertToClient("lead_1", {
        company_name: "Custom Name",
      });

      expect(apiClient.post).toHaveBeenCalledWith("/leads/lead_1/convert", {
        company_name: "Custom Name",
      });
    });

    it("throws when conversion fails for already-converted lead", async () => {
      (apiClient.post as ReturnType<typeof vi.fn>).mockRejectedValue(
        new Error("Lead already converted")
      );

      await expect(
        useLeadStore.getState().convertToClient("lead_1")
      ).rejects.toThrow("Lead already converted");

      expect(useLeadStore.getState().error).toBe("Lead already converted");
    });
  });

  describe("activities", () => {
    it("fetches activities for a lead", async () => {
      (apiClient.get as ReturnType<typeof vi.fn>).mockResolvedValue({
        data: { data: [baseActivity] },
      });

      const activities = await useLeadStore.getState().fetchActivities("lead_1");

      expect(activities).toHaveLength(1);
      expect(activities[0].id).toBe("act_1");
      expect(apiClient.get).toHaveBeenCalledWith("/leads/lead_1/activities");
    });

    it("returns empty array when activities data is absent", async () => {
      (apiClient.get as ReturnType<typeof vi.fn>).mockResolvedValue({
        data: {},
      });

      const activities = await useLeadStore.getState().fetchActivities("lead_1");

      expect(activities).toEqual([]);
    });

    it("throws when activity fetch fails", async () => {
      (apiClient.get as ReturnType<typeof vi.fn>).mockRejectedValue(
        new Error("Activities unavailable")
      );

      await expect(
        useLeadStore.getState().fetchActivities("lead_1")
      ).rejects.toThrow("Activities unavailable");
    });

    it("creates an activity for a lead", async () => {
      (apiClient.post as ReturnType<typeof vi.fn>).mockResolvedValue({
        data: baseActivity,
      });

      const activity = await useLeadStore.getState().createActivity("lead_1", {
        type: "call",
        content: "Initial call with prospect",
      });

      expect(activity?.id).toBe("act_1");
      expect(activity?.type).toBe("call");
      expect(apiClient.post).toHaveBeenCalledWith(
        "/leads/lead_1/activities",
        { type: "call", content: "Initial call with prospect" }
      );
    });

    it("throws when activity creation fails", async () => {
      (apiClient.post as ReturnType<typeof vi.fn>).mockRejectedValue(
        new Error("Create activity failed")
      );

      await expect(
        useLeadStore.getState().createActivity("lead_1", { type: "note" })
      ).rejects.toThrow("Create activity failed");
    });

    it("deletes an activity for a lead", async () => {
      (apiClient.delete as ReturnType<typeof vi.fn>).mockResolvedValue({});

      await useLeadStore.getState().deleteActivity("lead_1", "act_1");

      expect(apiClient.delete).toHaveBeenCalledWith(
        "/leads/lead_1/activities/act_1"
      );
    });

    it("throws when activity deletion fails", async () => {
      (apiClient.delete as ReturnType<typeof vi.fn>).mockRejectedValue(
        new Error("Delete activity failed")
      );

      await expect(
        useLeadStore.getState().deleteActivity("lead_1", "act_1")
      ).rejects.toThrow("Delete activity failed");
    });
  });
});

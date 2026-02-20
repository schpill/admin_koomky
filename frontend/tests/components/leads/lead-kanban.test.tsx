import { beforeEach, describe, expect, it, vi } from "vitest";
import { fireEvent, render, screen, waitFor } from "@testing-library/react";
import type { Lead } from "@/lib/stores/leads";

const fetchPipelineMock = vi.fn();
const updateStatusMock = vi.fn();
const useLeadStoreMock = vi.fn();

vi.mock("@/lib/stores/leads", () => ({
  useLeadStore: () => useLeadStoreMock(),
}));

import { LeadKanban } from "@/components/leads/lead-kanban";

const makeLead = (overrides: Partial<Lead> = {}): Lead => ({
  id: "lead_1",
  user_id: "user_1",
  company_name: "Acme Corp",
  first_name: "Alice",
  last_name: "Doe",
  full_name: "Alice Doe",
  email: "alice@acme.com",
  phone: null,
  source: "referral",
  status: "new",
  estimated_value: 5000,
  currency: "EUR",
  probability: 60,
  expected_close_date: "2026-06-30",
  notes: null,
  lost_reason: null,
  converted_at: null,
  can_convert: false,
  is_terminal: false,
  created_at: "2026-02-01T00:00:00Z",
  updated_at: "2026-02-01T00:00:00Z",
  ...overrides,
});

const mockPipeline = {
  columns: {
    new: [makeLead({ id: "lead_1", company_name: "Acme Corp" })],
    contacted: [
      makeLead({
        id: "lead_2",
        company_name: null,
        full_name: "Bob Smith",
        status: "contacted",
        estimated_value: 2000,
        probability: 40,
      }),
    ],
    qualified: [],
    proposal_sent: [],
    negotiating: [],
  },
  column_stats: {
    new: { count: 1, total_value: 5000 },
    contacted: { count: 1, total_value: 2000 },
    qualified: { count: 0, total_value: 0 },
    proposal_sent: { count: 0, total_value: 0 },
    negotiating: { count: 0, total_value: 0 },
  },
  total_pipeline_value: 7000,
};

describe("LeadKanban", () => {
  beforeEach(() => {
    vi.clearAllMocks();
    fetchPipelineMock.mockResolvedValue(undefined);
    updateStatusMock.mockResolvedValue(undefined);
    useLeadStoreMock.mockReturnValue({
      pipeline: mockPipeline,
      fetchPipeline: fetchPipelineMock,
      updateStatus: updateStatusMock,
      isLoading: false,
    });
  });

  describe("rendering", () => {
    it("fetches pipeline on mount", async () => {
      render(<LeadKanban />);

      await waitFor(() => {
        expect(fetchPipelineMock).toHaveBeenCalledTimes(1);
      });
    });

    it("renders all active pipeline column headers", () => {
      render(<LeadKanban />);

      expect(screen.getByText("New")).toBeInTheDocument();
      expect(screen.getByText("Contacted")).toBeInTheDocument();
      expect(screen.getByText("Qualified")).toBeInTheDocument();
      expect(screen.getByText("Proposal Sent")).toBeInTheDocument();
      expect(screen.getByText("Negotiating")).toBeInTheDocument();
    });

    it("does not render won or lost columns by default", () => {
      render(<LeadKanban />);

      expect(screen.queryByText("Won")).not.toBeInTheDocument();
      expect(screen.queryByText("Lost")).not.toBeInTheDocument();
    });

    it("renders won and lost columns when showTerminalColumns is true", () => {
      useLeadStoreMock.mockReturnValue({
        pipeline: {
          ...mockPipeline,
          columns: { ...mockPipeline.columns, won: [], lost: [] },
          column_stats: {
            ...mockPipeline.column_stats,
            won: { count: 0, total_value: 0 },
            lost: { count: 0, total_value: 0 },
          },
        },
        fetchPipeline: fetchPipelineMock,
        updateStatus: updateStatusMock,
        isLoading: false,
      });

      render(<LeadKanban showTerminalColumns />);

      expect(screen.getByText("Won")).toBeInTheDocument();
      expect(screen.getByText("Lost")).toBeInTheDocument();
    });

    it("renders lead cards with company name when present", () => {
      render(<LeadKanban />);

      expect(screen.getByText("Acme Corp")).toBeInTheDocument();
    });

    it("renders lead full name when company name is absent", () => {
      render(<LeadKanban />);

      expect(screen.getByText("Bob Smith")).toBeInTheDocument();
    });

    it("shows estimated value in lead card", () => {
      render(<LeadKanban />);

      const amounts = screen.getAllByText("€5,000.00");
      expect(amounts.length).toBeGreaterThanOrEqual(1);
    });

    it("shows probability percentage in lead card", () => {
      render(<LeadKanban />);

      expect(screen.getByText("60%")).toBeInTheDocument();
    });

    it("shows source badge on lead card", () => {
      render(<LeadKanban />);

      const badges = screen.getAllByText("referral");
      expect(badges.length).toBeGreaterThanOrEqual(1);
    });

    it("shows column lead count badge", () => {
      render(<LeadKanban />);

      const badges = screen.getAllByText("1");
      expect(badges.length).toBeGreaterThanOrEqual(2);
    });

    it("shows total pipeline value in header summary", () => {
      render(<LeadKanban />);

      expect(screen.getByText("€7,000.00")).toBeInTheDocument();
    });

    it("shows total lead count in header summary", () => {
      render(<LeadKanban />);

      expect(screen.getByText(/2\s+Leads/)).toBeInTheDocument();
    });

    it("shows empty state placeholder for columns with no leads", () => {
      render(<LeadKanban />);

      const emptySlots = screen.getAllByText("No leads");
      expect(emptySlots.length).toBeGreaterThanOrEqual(3);
    });

    it("shows loading state when pipeline is null and isLoading is true", () => {
      useLeadStoreMock.mockReturnValue({
        pipeline: null,
        fetchPipeline: fetchPipelineMock,
        updateStatus: updateStatusMock,
        isLoading: true,
      });

      render(<LeadKanban />);

      expect(screen.getByText("Loading pipeline...")).toBeInTheDocument();
    });

    it("shows no data message when pipeline is null and not loading", () => {
      useLeadStoreMock.mockReturnValue({
        pipeline: null,
        fetchPipeline: fetchPipelineMock,
        updateStatus: updateStatusMock,
        isLoading: false,
      });

      render(<LeadKanban />);

      expect(
        screen.getByText("No pipeline data available")
      ).toBeInTheDocument();
    });

    it("renders lead card without estimated_value when value is null", () => {
      useLeadStoreMock.mockReturnValue({
        pipeline: {
          ...mockPipeline,
          columns: {
            ...mockPipeline.columns,
            new: [
              makeLead({
                id: "lead_no_value",
                company_name: "No Value Co",
                estimated_value: null,
                probability: null,
              }),
            ],
          },
        },
        fetchPipeline: fetchPipelineMock,
        updateStatus: updateStatusMock,
        isLoading: false,
      });

      render(<LeadKanban />);

      expect(screen.getByText("No Value Co")).toBeInTheDocument();
      expect(screen.queryByText("€0.00")).not.toBeInTheDocument();
    });

    it("renders lead link pointing to lead detail page", () => {
      render(<LeadKanban />);

      const link = screen.getByRole("link", { name: "Acme Corp" });
      expect(link).toHaveAttribute("href", "/leads/lead_1");
    });
  });

  describe("column total value", () => {
    it("shows column total value when total_value is non-zero", () => {
      render(<LeadKanban />);

      // €5,000.00 appears both in the column stat header and in the lead card
      const amounts = screen.getAllByText("€5,000.00");
      expect(amounts.length).toBeGreaterThanOrEqual(2);
    });
  });

  describe("drag and drop", () => {
    it("calls updateStatus when card is dropped in a different column", async () => {
      const onLeadClick = vi.fn();
      render(<LeadKanban onLeadClick={onLeadClick} />);

      const card = screen
        .getByText("Acme Corp")
        .closest("[draggable]") as HTMLElement;
      const contactedColumn = screen
        .getByText("Contacted")
        .closest("div[class]") as HTMLElement;

      fireEvent.dragStart(card);
      fireEvent.dragOver(contactedColumn, { preventDefault: vi.fn() });
      fireEvent.drop(contactedColumn, { preventDefault: vi.fn() });

      await waitFor(() => {
        expect(updateStatusMock).toHaveBeenCalledWith("lead_1", "contacted");
      });
    });

    it("does not call updateStatus when dropped in the same column", async () => {
      render(<LeadKanban />);

      const card = screen
        .getByText("Acme Corp")
        .closest("[draggable]") as HTMLElement;
      const newColumnHeader = screen.getByText("New");
      const newColumn = newColumnHeader.closest("div[class]") as HTMLElement;

      fireEvent.dragStart(card);
      fireEvent.dragOver(newColumn, { preventDefault: vi.fn() });
      fireEvent.drop(newColumn, { preventDefault: vi.fn() });

      await waitFor(() => {
        expect(updateStatusMock).not.toHaveBeenCalled();
      });
    });

    it("clears dragged state after drag ends", () => {
      render(<LeadKanban />);

      const card = screen
        .getByText("Acme Corp")
        .closest("[draggable]") as HTMLElement;

      fireEvent.dragStart(card);
      fireEvent.dragEnd(card);

      expect(card).not.toHaveClass("opacity-50");
    });
  });

  describe("card interactions", () => {
    it("calls onLeadClick when card is clicked with handler", () => {
      const onLeadClick = vi.fn();
      render(<LeadKanban onLeadClick={onLeadClick} />);

      const link = screen.getByRole("link", { name: "Acme Corp" });
      fireEvent.click(link);

      expect(onLeadClick).toHaveBeenCalledWith(
        expect.objectContaining({ id: "lead_1" })
      );
    });

    it("does not propagate to link navigation when onLeadClick is provided", () => {
      const onLeadClick = vi.fn();
      render(<LeadKanban onLeadClick={onLeadClick} />);

      const link = screen.getByRole("link", { name: "Acme Corp" });
      const clickEvent = new MouseEvent("click", {
        bubbles: true,
        cancelable: true,
      });
      link.dispatchEvent(clickEvent);

      expect(onLeadClick).toHaveBeenCalled();
    });

    it("shows full_name subtitle when company_name is set", () => {
      render(<LeadKanban />);

      const fullNameSubtitle = screen.queryByText("Alice Doe");
      expect(fullNameSubtitle).toBeInTheDocument();
    });
  });
});

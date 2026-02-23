import { render, screen, fireEvent, waitFor } from "@testing-library/react";
import { describe, it, expect, vi, beforeEach } from "vitest";
import { TicketFormDialog } from "@/components/tickets/ticket-form-dialog";
import { I18nProvider } from "@/components/providers/i18n-provider";

vi.mock("@/lib/api", () => ({
  apiClient: {
    get: vi.fn().mockResolvedValue({ data: { data: [] } }),
  },
}));

function renderWithProviders(ui: React.ReactElement) {
  return render(<I18nProvider initialLocale="en">{ui}</I18nProvider>);
}

describe("TicketFormDialog", () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it("renders required fields", () => {
    renderWithProviders(
      <TicketFormDialog open={true} onOpenChange={vi.fn()} onSubmit={vi.fn()} />
    );
    expect(screen.getByLabelText(/title/i)).toBeInTheDocument();
    expect(screen.getByLabelText(/description/i)).toBeInTheDocument();
    expect(screen.getByText("Priority")).toBeInTheDocument();
  });

  it("shows validation error when title is empty", async () => {
    renderWithProviders(
      <TicketFormDialog open={true} onOpenChange={vi.fn()} onSubmit={vi.fn()} />
    );
    fireEvent.click(screen.getByRole("button", { name: /create ticket/i }));
    await waitFor(() => {
      expect(screen.getByText(/title is required/i)).toBeInTheDocument();
    });
  });

  it("renders project field", () => {
    renderWithProviders(
      <TicketFormDialog open={true} onOpenChange={vi.fn()} onSubmit={vi.fn()} />
    );
    expect(screen.getByText("Project")).toBeInTheDocument();
  });

  it('shows "Divers" as project placeholder when no client selected', () => {
    renderWithProviders(
      <TicketFormDialog open={true} onOpenChange={vi.fn()} onSubmit={vi.fn()} />
    );
    // The project selector shows "Divers" placeholder when no client selected
    const comboboxes = screen.getAllByRole("combobox");
    // Find the project combobox (should contain "Divers" text)
    const projectCombobox = comboboxes.find((cb) =>
      cb.textContent?.includes("Divers")
    );
    expect(projectCombobox).toBeDefined();
  });

  it("calls onSubmit with correct data", async () => {
    const onSubmit = vi.fn();
    renderWithProviders(
      <TicketFormDialog
        open={true}
        onOpenChange={vi.fn()}
        onSubmit={onSubmit}
      />
    );
    fireEvent.change(screen.getByLabelText(/title/i), {
      target: { value: "My ticket" },
    });
    fireEvent.change(screen.getByLabelText(/description/i), {
      target: { value: "My description" },
    });
    fireEvent.click(screen.getByRole("button", { name: /create ticket/i }));
    await waitFor(() => {
      expect(onSubmit).toHaveBeenCalledWith(
        expect.objectContaining({
          title: "My ticket",
          description: "My description",
        })
      );
    });
  });
});

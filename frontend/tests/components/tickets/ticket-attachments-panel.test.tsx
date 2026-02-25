import { render, screen, fireEvent } from "@testing-library/react";
import { describe, it, expect, vi } from "vitest";
import { TicketAttachmentsPanel } from "@/components/tickets/ticket-attachments-panel";
import { I18nProvider } from "@/components/providers/i18n-provider";

const mockDoc = { id: "d1", title: "test.pdf", file_size: 1024 };

function renderWithProviders(ui: React.ReactElement) {
  return render(<I18nProvider initialLocale="en">{ui}</I18nProvider>);
}

describe("TicketAttachmentsPanel", () => {
  it("shows empty state when no documents", () => {
    renderWithProviders(
      <TicketAttachmentsPanel
        ticketId="t1"
        documents={[]}
        onDetach={vi.fn()}
        onUpload={vi.fn()}
        onAttach={vi.fn()}
      />
    );
    expect(screen.getByText(/no attachments/i)).toBeInTheDocument();
  });

  it("renders document list", () => {
    renderWithProviders(
      <TicketAttachmentsPanel
        ticketId="t1"
        documents={[mockDoc]}
        onDetach={vi.fn()}
        onUpload={vi.fn()}
        onAttach={vi.fn()}
      />
    );
    expect(screen.getByText("test.pdf")).toBeInTheDocument();
  });

  it("calls onDetach when detach button clicked", () => {
    const onDetach = vi.fn();
    renderWithProviders(
      <TicketAttachmentsPanel
        ticketId="t1"
        documents={[mockDoc]}
        onDetach={onDetach}
        onUpload={vi.fn()}
        onAttach={vi.fn()}
      />
    );
    fireEvent.click(screen.getByLabelText("Detach"));
    expect(onDetach).toHaveBeenCalledWith("d1");
  });

  it("renders upload button", () => {
    renderWithProviders(
      <TicketAttachmentsPanel
        ticketId="t1"
        documents={[]}
        onDetach={vi.fn()}
        onUpload={vi.fn()}
        onAttach={vi.fn()}
      />
    );
    expect(screen.getByText(/upload attachment/i)).toBeInTheDocument();
  });

  it("download link present for each document", () => {
    renderWithProviders(
      <TicketAttachmentsPanel
        ticketId="t1"
        documents={[mockDoc]}
        onDetach={vi.fn()}
        onUpload={vi.fn()}
        onAttach={vi.fn()}
      />
    );
    const downloadLink = screen.getByLabelText("Download").closest("a");
    expect(downloadLink).toHaveAttribute(
      "href",
      "/api/v1/documents/d1/download"
    );
  });
});

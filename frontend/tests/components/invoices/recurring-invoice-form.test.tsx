import { describe, expect, it, vi } from "vitest";
import { fireEvent, render, screen } from "@testing-library/react";
import { RecurringInvoiceForm } from "@/components/invoices/recurring-invoice-form";

describe("RecurringInvoiceForm", () => {
  const clients = [{ id: "cli_1", name: "Acme" }];

  it("shows day-of-month field for monthly frequency", () => {
    render(
      <RecurringInvoiceForm clients={clients} onSubmit={vi.fn()} submitLabel="Save" />
    );

    expect(screen.getByLabelText("Day of month")).toBeInTheDocument();
  });

  it("hides day-of-month field for weekly frequency", () => {
    render(
      <RecurringInvoiceForm clients={clients} onSubmit={vi.fn()} submitLabel="Save" />
    );

    fireEvent.change(screen.getByLabelText("Frequency"), {
      target: { value: "weekly" },
    });

    expect(screen.queryByLabelText("Day of month")).not.toBeInTheDocument();
  });

  it("validates end date is after start date", async () => {
    render(
      <RecurringInvoiceForm clients={clients} onSubmit={vi.fn()} submitLabel="Save" />
    );

    fireEvent.change(screen.getByLabelText("Client"), {
      target: { value: "cli_1" },
    });
    fireEvent.change(screen.getByLabelText("Profile name"), {
      target: { value: "Monthly" },
    });
    fireEvent.change(screen.getByLabelText("Start date"), {
      target: { value: "2026-02-10" },
    });
    fireEvent.change(screen.getByLabelText("End date (optional)"), {
      target: { value: "2026-02-01" },
    });

    fireEvent.submit(screen.getByRole("button", { name: "Save" }));

    expect(
      await screen.findByText("End date must be after start date")
    ).toBeInTheDocument();
  });

  it("submits sanitized payload", async () => {
    const onSubmit = vi.fn();

    render(
      <RecurringInvoiceForm
        clients={clients}
        onSubmit={onSubmit}
        submitLabel="Save"
      />
    );

    fireEvent.change(screen.getByLabelText("Client"), {
      target: { value: "cli_1" },
    });
    fireEvent.change(screen.getByLabelText("Profile name"), {
      target: { value: "  Monthly retainer  " },
    });
    fireEvent.change(screen.getAllByLabelText("Description")[0], {
      target: { value: "  Retainer fee  " },
    });

    fireEvent.submit(screen.getByRole("button", { name: "Save" }));

    expect(onSubmit).toHaveBeenCalledTimes(1);
    expect(onSubmit.mock.calls[0][0].name).toBe("Monthly retainer");
    expect(onSubmit.mock.calls[0][0].line_items[0].description).toBe(
      "Retainer fee"
    );
  });
});

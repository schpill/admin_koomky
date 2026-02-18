import { describe, expect, it, vi } from "vitest";
import { fireEvent, render, screen } from "@testing-library/react";
import { RecurringInvoiceForm } from "@/components/invoices/recurring-invoice-form";

describe("RecurringInvoiceForm", () => {
  const clients = [{ id: "cli_1", name: "Acme" }];

  it("shows day-of-month field for monthly frequency", () => {
    render(
      <RecurringInvoiceForm
        clients={clients}
        onSubmit={vi.fn()}
        submitLabel="Save"
      />
    );

    expect(screen.getByLabelText("Day of month")).toBeInTheDocument();
  });

  it("hides day-of-month field for weekly frequency", () => {
    render(
      <RecurringInvoiceForm
        clients={clients}
        onSubmit={vi.fn()}
        submitLabel="Save"
      />
    );

    fireEvent.change(screen.getByLabelText("Frequency"), {
      target: { value: "weekly" },
    });

    expect(screen.queryByLabelText("Day of month")).not.toBeInTheDocument();
  });

  it("validates end date is after start date", async () => {
    render(
      <RecurringInvoiceForm
        clients={clients}
        onSubmit={vi.fn()}
        submitLabel="Save"
      />
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

  it("validates required client and profile name", async () => {
    render(
      <RecurringInvoiceForm
        clients={clients}
        onSubmit={vi.fn()}
        submitLabel="Save"
      />
    );

    fireEvent.submit(screen.getByRole("button", { name: "Save" }));
    expect(
      await screen.findByText("Please select a client")
    ).toBeInTheDocument();

    fireEvent.change(screen.getByLabelText("Client"), {
      target: { value: "cli_1" },
    });
    fireEvent.submit(screen.getByRole("button", { name: "Save" }));

    expect(
      await screen.findByText("Profile name is required")
    ).toBeInTheDocument();
  });

  it("requires at least one non-empty line item", async () => {
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
      target: { value: "Recurring profile" },
    });
    fireEvent.change(screen.getAllByLabelText("Description")[0], {
      target: { value: "   " },
    });

    fireEvent.submit(screen.getByRole("button", { name: "Save" }));

    expect(
      await screen.findByText("At least one line item is required")
    ).toBeInTheDocument();
    expect(onSubmit).not.toHaveBeenCalled();
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

  it("supports fixed discount, weekly frequency and cancel button", async () => {
    const onSubmit = vi.fn();
    const onCancel = vi.fn();

    render(
      <RecurringInvoiceForm
        clients={clients}
        onSubmit={onSubmit}
        submitLabel="Save"
        onCancel={onCancel}
      />
    );

    fireEvent.change(screen.getByLabelText("Client"), {
      target: { value: "cli_1" },
    });
    fireEvent.change(screen.getByLabelText("Profile name"), {
      target: { value: "Weekly support" },
    });
    fireEvent.change(screen.getByLabelText("Frequency"), {
      target: { value: "weekly" },
    });
    fireEvent.change(screen.getByLabelText("Discount type"), {
      target: { value: "fixed" },
    });
    fireEvent.change(screen.getByLabelText("Discount value"), {
      target: { value: "25" },
    });
    fireEvent.change(screen.getByLabelText("Max occurrences"), {
      target: { value: "12" },
    });
    fireEvent.change(screen.getByLabelText("Currency"), {
      target: { value: "usd" },
    });
    fireEvent.change(screen.getByLabelText("Auto send generated invoices"), {
      target: { value: "yes" },
    });
    fireEvent.change(screen.getAllByLabelText("Description")[0], {
      target: { value: "Weekly maintenance" },
    });

    fireEvent.submit(screen.getByRole("button", { name: "Save" }));

    expect(onSubmit).toHaveBeenCalledTimes(1);
    expect(onSubmit.mock.calls[0][0].day_of_month).toBeNull();
    expect(onSubmit.mock.calls[0][0].discount_percent).toBeNull();
    expect(onSubmit.mock.calls[0][0].max_occurrences).toBe(12);
    expect(onSubmit.mock.calls[0][0].auto_send).toBe(true);
    expect(onSubmit.mock.calls[0][0].currency).toBe("USD");

    fireEvent.click(screen.getByRole("button", { name: "Cancel" }));
    expect(onCancel).toHaveBeenCalledTimes(1);
  });

  it("shows submitting state", () => {
    render(
      <RecurringInvoiceForm
        clients={clients}
        onSubmit={vi.fn()}
        submitLabel="Save"
        isSubmitting
      />
    );

    expect(screen.getByRole("button", { name: "Saving..." })).toBeDisabled();
  });
});

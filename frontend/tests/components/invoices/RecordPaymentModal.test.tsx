import { describe, expect, it, vi } from "vitest";
import { fireEvent, render, screen, waitFor } from "@testing-library/react";
import type { ReactNode } from "react";
import { RecordPaymentModal } from "@/components/invoices/record-payment-modal";
import { I18nProvider } from "@/components/providers/i18n-provider";

function renderWithI18n(component: ReactNode) {
  return render(<I18nProvider initialLocale="en">{component}</I18nProvider>);
}

describe("RecordPaymentModal", () => {
  it("validates amount against remaining balance", async () => {
    const onSubmit = vi.fn();

    renderWithI18n(
      <RecordPaymentModal
        open
        onOpenChange={vi.fn()}
        invoiceTotal={1000}
        amountPaid={200}
        onSubmit={onSubmit}
      />
    );

    fireEvent.change(screen.getByLabelText("Amount"), {
      target: { value: "900" },
    });
    fireEvent.click(screen.getByRole("button", { name: "Record payment" }));

    await waitFor(() => {
      expect(
        screen.getByText("Amount cannot exceed remaining balance (800.00)")
      ).toBeInTheDocument();
    });

    expect(onSubmit).not.toHaveBeenCalled();
  });

  it("submits payment and displays remaining amount", async () => {
    const onSubmit = vi.fn();

    renderWithI18n(
      <RecordPaymentModal
        open
        onOpenChange={vi.fn()}
        invoiceTotal={1000}
        amountPaid={200}
        onSubmit={onSubmit}
      />
    );

    expect(screen.getByText("Remaining after payment: 800.00 EUR")).toBeInTheDocument();

    fireEvent.change(screen.getByLabelText("Amount"), {
      target: { value: "300" },
    });
    fireEvent.change(screen.getByLabelText("Payment date"), {
      target: { value: "2026-02-16" },
    });

    fireEvent.click(screen.getByRole("button", { name: "Record payment" }));

    await waitFor(() => {
      expect(onSubmit).toHaveBeenCalledWith({
        amount: 300,
        payment_date: "2026-02-16",
        payment_method: "",
        reference: "",
        notes: "",
      });
    });
  });
});

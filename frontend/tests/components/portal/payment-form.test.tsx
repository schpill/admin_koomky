import { describe, it, expect, vi, beforeEach } from "vitest";
import { fireEvent, render, screen, waitFor } from "@testing-library/react";
import { PaymentForm } from "@/components/portal/payment-form";

const confirmCardPayment = vi.fn();

vi.mock("@stripe/react-stripe-js", () => ({
  CardElement: (props: Record<string, unknown>) => (
    <div data-testid="card-element" {...props} />
  ),
  useStripe: () => ({
    confirmCardPayment,
  }),
  useElements: () => ({
    getElement: () => ({ id: "card" }),
  }),
}));

describe("PaymentForm", () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it("handles payment success", async () => {
    confirmCardPayment.mockResolvedValue({
      paymentIntent: { status: "succeeded" },
    });

    const onSuccess = vi.fn();

    render(
      <PaymentForm
        amount={120}
        currency="EUR"
        clientSecret="pi_secret"
        onSuccess={onSuccess}
      />
    );

    fireEvent.click(screen.getByRole("button", { name: /pay now/i }));

    await waitFor(() => {
      expect(confirmCardPayment).toHaveBeenCalledTimes(1);
      expect(onSuccess).toHaveBeenCalledTimes(1);
    });
  });

  it("handles payment failure", async () => {
    confirmCardPayment.mockResolvedValue({
      error: { message: "Card declined" },
    });

    const onFailure = vi.fn();

    render(
      <PaymentForm
        amount={75}
        currency="EUR"
        clientSecret="pi_secret"
        onFailure={onFailure}
      />
    );

    fireEvent.click(screen.getByRole("button", { name: /pay now/i }));

    await waitFor(() => {
      expect(onFailure).toHaveBeenCalledWith("Card declined");
      expect(screen.getByText("Card declined")).toBeInTheDocument();
    });
  });
});

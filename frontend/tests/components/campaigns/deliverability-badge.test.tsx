import { render, screen } from "@testing-library/react";
import { describe, expect, it } from "vitest";
import { DeliverabilityBadge } from "@/components/campaigns/deliverability-badge";

describe("DeliverabilityBadge", () => {
  it("renders the score and healthy state", () => {
    render(<DeliverabilityBadge score={91} issues={[]} />);

    expect(
      screen.getByLabelText("Deliverability score 91")
    ).toBeInTheDocument();
    expect(screen.getByText(/No issues detected/i)).toBeInTheDocument();
  });

  it("renders listed issues", () => {
    render(
      <DeliverabilityBadge
        score={40}
        issues={[
          { severity: "error", message: "Missing unsubscribe link" },
          { severity: "warning", message: "Spam words detected" },
        ]}
      />
    );

    expect(screen.getByText("Missing unsubscribe link")).toBeInTheDocument();
    expect(screen.getByText("Spam words detected")).toBeInTheDocument();
  });
});

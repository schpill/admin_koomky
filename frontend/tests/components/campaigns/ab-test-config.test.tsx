import { describe, expect, it, vi } from "vitest";
import { fireEvent, render, screen } from "@testing-library/react";
import { AbTestConfig } from "@/components/campaigns/ab-test-config";

describe("AbTestConfig", () => {
  it("shows variant forms when enabled", () => {
    render(
      <AbTestConfig
        enabled
        onToggle={vi.fn()}
        variants={[
          { label: "A", subject: "A", content: "A", send_percent: 50 },
          { label: "B", subject: "B", content: "B", send_percent: 50 },
        ]}
        onChangeVariant={vi.fn()}
        winnerCriteria="open_rate"
        onWinnerCriteriaChange={vi.fn()}
        autoSelectAfterHours={24}
        onAutoSelectAfterHoursChange={vi.fn()}
      />
    );

    expect(screen.getByText("Variante A")).toBeInTheDocument();
    expect(screen.getByText("Variante B")).toBeInTheDocument();
  });

  it("hides auto-select input for manual winner criteria", () => {
    render(
      <AbTestConfig
        enabled
        onToggle={vi.fn()}
        variants={[
          { label: "A", subject: "A", content: "A", send_percent: 50 },
          { label: "B", subject: "B", content: "B", send_percent: 50 },
        ]}
        onChangeVariant={vi.fn()}
        winnerCriteria="manual"
        onWinnerCriteriaChange={vi.fn()}
        autoSelectAfterHours={24}
        onAutoSelectAfterHoursChange={vi.fn()}
      />
    );

    expect(
      screen.queryByLabelText("Sélection automatique après N heures")
    ).not.toBeInTheDocument();
  });

  it("calls toggle callback", () => {
    const onToggle = vi.fn();

    render(
      <AbTestConfig
        enabled={false}
        onToggle={onToggle}
        variants={[
          { label: "A", subject: "A", content: "A", send_percent: 50 },
          { label: "B", subject: "B", content: "B", send_percent: 50 },
        ]}
        onChangeVariant={vi.fn()}
        winnerCriteria="open_rate"
        onWinnerCriteriaChange={vi.fn()}
        autoSelectAfterHours={24}
        onAutoSelectAfterHoursChange={vi.fn()}
      />
    );

    fireEvent.click(screen.getByLabelText("Activer le A/B test"));
    expect(onToggle).toHaveBeenCalledWith(true);
  });
});

import { describe, expect, it, vi } from "vitest";
import { fireEvent, render, screen } from "@testing-library/react";
import { AbTestResults } from "@/components/campaigns/ab-test-results";

describe("AbTestResults", () => {
  it("renders winner badge and stats", () => {
    render(
      <AbTestResults
        campaign={{
          id: "camp_1",
          name: "AB",
          type: "email",
          status: "sending",
          content: "x",
          ab_winner_criteria: "manual",
          ab_winner_variant_id: "var_a",
        }}
        variants={[
          {
            label: "A",
            sent_count: 10,
            open_count: 5,
            click_count: 2,
            open_rate: 50,
            click_rate: 20,
            is_winner: true,
          },
        ]}
      />
    );

    expect(screen.getByText("Gagnant")).toBeInTheDocument();
    expect(screen.getByText("Envoyés: 10")).toBeInTheDocument();
  });

  it("allows selecting manual winner when no winner selected", () => {
    const onSelectWinner = vi.fn().mockResolvedValue(undefined);

    render(
      <AbTestResults
        campaign={{
          id: "camp_1",
          name: "AB",
          type: "email",
          status: "sending",
          content: "x",
          ab_winner_criteria: "manual",
          ab_winner_variant_id: null,
        }}
        variants={[
          {
            label: "A",
            sent_count: 10,
            open_count: 5,
            click_count: 2,
            open_rate: 50,
            click_rate: 20,
            is_winner: false,
          },
        ]}
        onSelectWinner={onSelectWinner}
      />
    );

    fireEvent.click(
      screen.getByRole("button", { name: "Sélectionner comme gagnant" })
    );
    expect(onSelectWinner).toHaveBeenCalledWith("A");
  });
});

import { beforeEach, describe, expect, it, vi } from "vitest";
import { fireEvent, render, screen } from "@testing-library/react";
import { PersonalizationVariablesPanel } from "@/components/campaigns/personalization-variables-panel";

describe("PersonalizationVariablesPanel", () => {
  beforeEach(() => {
    Object.assign(navigator, {
      clipboard: {
        writeText: vi.fn().mockResolvedValue(undefined),
      },
    });
  });

  it("renders variables and allows insert callback", () => {
    const onInsert = vi.fn();

    render(<PersonalizationVariablesPanel onInsert={onInsert} />);

    expect(screen.getByText("{{first_name}}")).toBeInTheDocument();

    const insertButtons = screen.getAllByRole("button", { name: "Insérer" });
    fireEvent.click(insertButtons[0]);

    expect(onInsert).toHaveBeenCalled();
  });
});

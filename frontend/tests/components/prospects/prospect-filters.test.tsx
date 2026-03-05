import { fireEvent, render, screen, waitFor } from "@testing-library/react";
import { describe, expect, it, vi } from "vitest";
import { ProspectFilters } from "@/components/prospects/prospect-filters";

vi.mock("@/lib/api", () => ({
  apiClient: {
    get: vi.fn().mockResolvedValue({ data: ["Wedding Planner"] }),
  },
}));

describe("ProspectFilters", () => {
  it("updates filters and resets", async () => {
    const onChange = vi.fn();
    render(<ProspectFilters value={{}} onChange={onChange} />);

    await waitFor(() => expect(screen.getByPlaceholderText(/Rechercher/i)).toBeInTheDocument());

    fireEvent.change(screen.getByPlaceholderText(/Rechercher/i), {
      target: { value: "acme" },
    });
    fireEvent.click(screen.getByText(/Réinitialiser/i));

    expect(onChange).toHaveBeenCalled();
  });
});

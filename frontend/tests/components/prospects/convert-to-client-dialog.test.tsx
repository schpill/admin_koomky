import { fireEvent, render, screen } from "@testing-library/react";
import { describe, expect, it, vi } from "vitest";
import { ConvertToClientDialog } from "@/components/prospects/convert-to-client-dialog";

describe("ConvertToClientDialog", () => {
  it("confirms conversion", () => {
    const onConfirm = vi.fn();
    render(
      <ConvertToClientDialog open onOpenChange={() => {}} onConfirm={onConfirm} />
    );

    fireEvent.click(screen.getByText(/Confirmer/i));
    expect(onConfirm).toHaveBeenCalled();
  });
});

import { fireEvent, render, screen } from "@testing-library/react";
import { describe, expect, it, vi } from "vitest";
import { ProspectTable } from "@/components/prospects/prospect-table";

describe("ProspectTable", () => {
  it("selects row and triggers actions", () => {
    const onSelect = vi.fn();
    const onConvert = vi.fn();
    const onCreateCampaign = vi.fn();

    render(
      <ProspectTable
        prospects={[{ id: "c1", name: "Acme", status: "prospect" }]}
        selectedIds={[]}
        onSelect={onSelect}
        onConvert={onConvert}
        onCreateCampaign={onCreateCampaign}
      />
    );

    fireEvent.click(screen.getByRole("checkbox"));
    fireEvent.click(screen.getByText(/Convertir/i));

    expect(onSelect).toHaveBeenCalled();
    expect(onConvert).toHaveBeenCalledWith("c1");
  });
});

import { describe, it, expect, vi } from "vitest";
import { fireEvent, render, screen } from "@testing-library/react";
import { PortalHeader } from "@/components/portal/portal-header";

vi.mock("next/navigation", () => ({
  usePathname: () => "/portal/dashboard",
}));

describe("PortalHeader", () => {
  it("renders client name and triggers logout", () => {
    const onLogout = vi.fn();

    render(
      <PortalHeader
        clientName="Acme Client"
        customLogo={null}
        customColor="#123456"
        onLogout={onLogout}
      />
    );

    expect(screen.getByText("Acme Client")).toBeInTheDocument();
    expect(screen.getByRole("link", { name: "Dashboard" })).toBeVisible();

    fireEvent.click(screen.getByRole("button", { name: /logout/i }));
    expect(onLogout).toHaveBeenCalledTimes(1);
  });
});

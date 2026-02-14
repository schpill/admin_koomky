import { render, screen } from "@testing-library/react";
import { expect, test, vi } from "vitest";
import Page from "../app/(dashboard)/page";

// Mock the layout to avoid sidebar/header dependencies
vi.mock("@/components/layout/dashboard-layout", () => ({
  DashboardLayout: ({ children }: { children: React.ReactNode }) => <div>{children}</div>,
}));

test("Dashboard page renders heading", () => {
  render(<Page />);
  expect(screen.getByText("Dashboard")).toBeInTheDocument();
});

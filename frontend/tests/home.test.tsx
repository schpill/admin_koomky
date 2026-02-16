import { render, screen } from "@testing-library/react";
import { expect, test, vi } from "vitest";
import Page from "../app/(dashboard)/page";
import { I18nProvider } from "@/components/providers/i18n-provider";

// Mock the layout to avoid sidebar/header dependencies
vi.mock("@/components/layout/dashboard-layout", () => ({
  DashboardLayout: ({ children }: { children: React.ReactNode }) => (
    <div>{children}</div>
  ),
}));

test("Dashboard page renders heading", () => {
  render(
    <I18nProvider initialLocale="fr">
      <Page />
    </I18nProvider>
  );
  expect(screen.getByText("Tableau de bord")).toBeInTheDocument();
});

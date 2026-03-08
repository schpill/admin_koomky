import { render, screen } from "@testing-library/react";
import { describe, expect, it, vi } from "vitest";
import { I18nProvider } from "@/components/providers/i18n-provider";

vi.mock("@/components/layout/dashboard-layout", () => ({
  DashboardLayout: ({ children }: { children: React.ReactNode }) => (
    <div>{children}</div>
  ),
}));

import DocsHomePage from "@/app/(dashboard)/docs/page";

describe("DocsHomePage", () => {
  it("renders the documentation landing page with all module cards", () => {
    render(
      <I18nProvider initialLocale="fr">
        <DocsHomePage />
      </I18nProvider>
    );

    expect(
      screen.getByRole("heading", { name: /Documentation intégrée/i })
    ).toBeInTheDocument();
    expect(screen.getByText(/22 modules/)).toBeInTheDocument();
    expect(screen.getAllByRole("link").length).toBeGreaterThanOrEqual(22);
  });
});

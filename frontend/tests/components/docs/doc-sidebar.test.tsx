import { render, screen } from "@testing-library/react";
import { describe, expect, it, vi } from "vitest";
import { I18nProvider } from "@/components/providers/i18n-provider";

vi.mock("next/navigation", () => ({
  usePathname: () => "/docs/invoices",
}));

import { DOC_MODULES } from "@/lib/docs/config";
import { DocSidebar } from "@/components/docs/doc-sidebar";

describe("DocSidebar", () => {
  it("renders every documented module and marks the active link", () => {
    render(
      <I18nProvider initialLocale="fr">
        <DocSidebar />
      </I18nProvider>
    );

    expect(DOC_MODULES).toHaveLength(22);
    expect(screen.getByRole("link", { name: /Factures/i })).toHaveAttribute(
      "aria-current",
      "page"
    );
    expect(screen.getByRole("link", { name: /Démarrage rapide/i })).toBeInTheDocument();
    expect(screen.getByRole("link", { name: /Paramètres/i })).toBeInTheDocument();
  });
});

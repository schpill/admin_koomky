import { render, screen } from "@testing-library/react";
import { describe, expect, it } from "vitest";
import { I18nProvider } from "@/components/providers/i18n-provider";
import { PageBreadcrumbs } from "@/components/layout/page-breadcrumbs";

function renderEN(pathname: string) {
  return render(
    <I18nProvider initialLocale="en">
      <PageBreadcrumbs pathname={pathname} />
    </I18nProvider>
  );
}

function renderFR(pathname: string) {
  return render(
    <I18nProvider initialLocale="fr">
      <PageBreadcrumbs pathname={pathname} />
    </I18nProvider>
  );
}

describe("PageBreadcrumbs", () => {
  it("renders crumbs for nested detail/edit routes (EN)", () => {
    renderEN("/clients/4f0d7d20/edit");

    expect(
      screen.getByRole("navigation", { name: "Breadcrumb" })
    ).toBeInTheDocument();
    expect(screen.getByRole("link", { name: "Clients" })).toBeInTheDocument();
    expect(screen.getByText("4f0d7d20")).toBeInTheDocument();
    expect(screen.getByText("Edit")).toBeInTheDocument();
  });

  it("translates segments into French (FR)", () => {
    renderFR("/credit-notes");

    expect(screen.getByText("Avoirs")).toBeInTheDocument();
  });

  it("translates leads page into French", () => {
    renderFR("/leads/create");

    expect(screen.getByRole("link", { name: "Prospects" })).toBeInTheDocument();
    expect(screen.getByText("Créer")).toBeInTheDocument();
  });

  it("translates edit segment into French", () => {
    renderFR("/clients/4f0d7d20/edit");

    expect(screen.getByRole("link", { name: "Clients" })).toBeInTheDocument();
    expect(screen.getByText("Modifier")).toBeInTheDocument();
  });

  it("translates settings sub-pages into French", () => {
    renderFR("/settings/security");

    expect(screen.getByRole("link", { name: "Paramètres" })).toBeInTheDocument();
    expect(screen.getByText("Sécurité")).toBeInTheDocument();
  });

  it("returns null on dashboard root", () => {
    const { container } = renderEN("/");

    expect(container).toBeEmptyDOMElement();
  });

  it("keeps UUIDs and IDs as-is", () => {
    renderEN("/clients/4f0d7d20/edit");

    expect(screen.getByText("4f0d7d20")).toBeInTheDocument();
  });
});

import { render, screen } from "@testing-library/react";
import { describe, expect, it } from "vitest";
import { PageBreadcrumbs } from "@/components/layout/page-breadcrumbs";

describe("PageBreadcrumbs", () => {
  it("renders crumbs for nested detail/edit routes", () => {
    render(<PageBreadcrumbs pathname="/clients/4f0d7d20/edit" />);

    expect(
      screen.getByRole("navigation", { name: "Breadcrumb" })
    ).toBeInTheDocument();
    expect(screen.getByRole("link", { name: "Clients" })).toBeInTheDocument();
    expect(screen.getByText("4f0d7d20")).toBeInTheDocument();
    expect(screen.getByText("Edit")).toBeInTheDocument();
  });

  it("returns null on dashboard root", () => {
    const { container } = render(<PageBreadcrumbs pathname="/" />);

    expect(container).toBeEmptyDOMElement();
  });
});

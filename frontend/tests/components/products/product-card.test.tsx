import { describe, expect, it, vi } from "vitest";
import { render, screen } from "@testing-library/react";
import { ProductCard } from "@/components/products/product-card";

vi.mock("next/link", () => ({
  default: ({
    href,
    children,
  }: {
    href: string;
    children: React.ReactNode;
  }) => <a href={href}>{children}</a>,
}));

describe("ProductCard", () => {
  it("renders product information and campaign action for active product", () => {
    render(
      <ProductCard
        product={{
          id: "prod_1",
          name: "Formation Laravel",
          short_description: "Formation avancée",
          price: 1200,
          price_type: "fixed",
          currency_code: "EUR",
          duration: 3,
          duration_unit: "days",
          is_active: true,
          type: "training",
        }}
      />
    );

    expect(screen.getByText("Formation Laravel")).toBeInTheDocument();
    expect(screen.getByText("Formation avancée")).toBeInTheDocument();
    expect(
      screen.getByText(
        (content) => content.includes("1") && content.includes("200,00")
      )
    ).toBeInTheDocument();
    expect(screen.getByText("3 jours")).toBeInTheDocument();
    expect(screen.getByRole("link", { name: /campagne ia/i })).toHaveAttribute(
      "href",
      "/products/prod_1/campaigns/generate"
    );
  });

  it("shows archived badge and hides campaign button for inactive product", () => {
    render(
      <ProductCard
        product={{
          id: "prod_2",
          name: "Pack SEO",
          price: 300,
          price_type: "fixed",
          currency_code: "EUR",
          is_active: false,
          type: "service",
        }}
      />
    );

    expect(screen.getByText("Archivé")).toBeInTheDocument();
    expect(
      screen.queryByRole("link", { name: /campagne ia/i })
    ).not.toBeInTheDocument();
  });

  it("supports disabling campaign shortcut", () => {
    render(
      <ProductCard
        product={{
          id: "prod_3",
          name: "Abonnement support",
          price: 90,
          price_type: "fixed",
          currency_code: "EUR",
          is_active: true,
          type: "subscription",
        }}
        showGenerateCampaign={false}
      />
    );

    expect(
      screen.queryByRole("link", { name: /campagne ia/i })
    ).not.toBeInTheDocument();
  });
});

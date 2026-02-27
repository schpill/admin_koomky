import { describe, expect, it } from "vitest";
import { render, screen } from "@testing-library/react";
import { ProductTypeBadge } from "@/components/products/product-type-badge";

describe("ProductTypeBadge", () => {
  it("renders service badge", () => {
    render(<ProductTypeBadge type="service" />);
    expect(screen.getByText("Service")).toBeInTheDocument();
  });

  it("renders training badge", () => {
    render(<ProductTypeBadge type="training" />);
    expect(screen.getByText("Formation")).toBeInTheDocument();
  });

  it("renders product badge", () => {
    render(<ProductTypeBadge type="product" />);
    expect(screen.getByText("Produit")).toBeInTheDocument();
  });

  it("renders subscription badge", () => {
    render(<ProductTypeBadge type="subscription" />);
    expect(screen.getByText("Abonnement")).toBeInTheDocument();
  });
});

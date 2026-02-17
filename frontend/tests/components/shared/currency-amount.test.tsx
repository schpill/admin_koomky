import { describe, expect, it } from "vitest";
import { render, screen } from "@testing-library/react";
import { CurrencyAmount } from "@/components/shared/currency-amount";

describe("CurrencyAmount", () => {
  it("formats EUR and USD with two decimals", () => {
    render(<CurrencyAmount amount={1234.5} currency="EUR" locale="en-US" />);
    expect(screen.getByText("€1,234.50")).toBeInTheDocument();

    render(<CurrencyAmount amount={1234.5} currency="USD" locale="en-US" />);
    expect(screen.getByText("$1,234.50")).toBeInTheDocument();
  });

  it("formats JPY without decimals", () => {
    render(<CurrencyAmount amount={1234.5} currency="JPY" locale="en-US" />);
    expect(screen.getByText("¥1,235")).toBeInTheDocument();
  });

  it("supports locale-specific symbol placement", () => {
    render(<CurrencyAmount amount={1234.5} currency="EUR" locale="fr-FR" />);
    expect(screen.getByText(/1.*234.*50.*€/)).toBeInTheDocument();
  });
});

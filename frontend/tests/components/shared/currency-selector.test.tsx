import { describe, expect, it, vi } from "vitest";
import { fireEvent, render, screen } from "@testing-library/react";
import { CurrencySelector } from "@/components/shared/currency-selector";

describe("CurrencySelector", () => {
  const currencies = [
    {
      code: "EUR",
      name: "Euro",
      symbol: "€",
      decimal_places: 2,
      is_active: true,
    },
    {
      code: "USD",
      name: "US Dollar",
      symbol: "$",
      decimal_places: 2,
      is_active: true,
    },
    {
      code: "JPY",
      name: "Japanese Yen",
      symbol: "¥",
      decimal_places: 0,
      is_active: true,
    },
  ];

  it("filters currencies from the search input", () => {
    render(
      <CurrencySelector
        id="invoice-currency"
        label="Currency"
        value="EUR"
        currencies={currencies}
        onValueChange={vi.fn()}
      />
    );

    fireEvent.change(screen.getByLabelText("Search currency"), {
      target: { value: "yen" },
    });

    expect(
      screen.getByRole("option", { name: /jpy - japanese yen/i })
    ).toBeInTheDocument();
    expect(
      screen.queryByRole("option", { name: /usd - us dollar/i })
    ).not.toBeInTheDocument();
  });

  it("emits selected currency code", () => {
    const onValueChange = vi.fn();

    render(
      <CurrencySelector
        id="invoice-currency"
        label="Currency"
        value="EUR"
        currencies={currencies}
        onValueChange={onValueChange}
      />
    );

    fireEvent.change(screen.getByLabelText("Currency"), {
      target: { value: "USD" },
    });

    expect(onValueChange).toHaveBeenCalledWith("USD");
  });
});

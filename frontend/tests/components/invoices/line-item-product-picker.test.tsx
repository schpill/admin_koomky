import { beforeEach, describe, expect, it, vi } from "vitest";
import { fireEvent, render, screen } from "@testing-library/react";
import { LineItemProductPicker } from "@/components/invoices/line-item-product-picker";

const mockFetchProducts = vi.fn();
const mockUseProductsStore = vi.fn();

vi.mock("@/lib/stores/products", () => ({
  useProductsStore: () => mockUseProductsStore(),
}));

vi.mock("@/components/products/product-type-badge", () => ({
  ProductTypeBadge: ({ type }: { type: string }) => <span>{type}</span>,
}));

vi.mock("@/components/ui/popover", () => ({
  Popover: ({ children }: { children: React.ReactNode }) => (
    <div>{children}</div>
  ),
  PopoverTrigger: ({ children }: { children: React.ReactNode }) => (
    <>{children}</>
  ),
  PopoverContent: ({ children }: { children: React.ReactNode }) => (
    <div>{children}</div>
  ),
}));

vi.mock("@/components/ui/command", () => ({
  Command: ({ children }: { children: React.ReactNode }) => (
    <div>{children}</div>
  ),
  CommandInput: () => <input aria-label="Rechercher un produit" />,
  CommandList: ({ children }: { children: React.ReactNode }) => (
    <div>{children}</div>
  ),
  CommandEmpty: ({ children }: { children: React.ReactNode }) => (
    <div>{children}</div>
  ),
  CommandGroup: ({ children }: { children: React.ReactNode }) => (
    <div>{children}</div>
  ),
  CommandItem: ({
    children,
    value,
    onSelect,
  }: {
    children: React.ReactNode;
    value: string;
    onSelect?: (value: string) => void;
  }) => (
    <button type="button" onClick={() => onSelect?.(value)}>
      {children}
    </button>
  ),
}));

describe("LineItemProductPicker", () => {
  beforeEach(() => {
    vi.clearAllMocks();
    mockUseProductsStore.mockReturnValue({
      products: [
        {
          id: "prod_1",
          name: "Formation CRM",
          price: "1200.00",
          currency_code: "EUR",
          type: "training",
          description: "Description",
          vat_rate: "20.00",
        },
      ],
      fetchProducts: mockFetchProducts,
    });
  });

  it("loads products on mount", () => {
    render(<LineItemProductPicker value={null} onChange={vi.fn()} />);

    expect(mockFetchProducts).toHaveBeenCalledWith({ isActive: true });
  });

  it("selects a product and returns mapped payload", () => {
    const onChange = vi.fn();

    render(<LineItemProductPicker value={null} onChange={onChange} />);

    fireEvent.click(screen.getByText("Formation CRM"));

    expect(onChange).toHaveBeenCalledWith(
      "prod_1",
      expect.objectContaining({
        id: "prod_1",
        name: "Formation CRM",
        price: 1200,
        vat_rate: 20,
      })
    );
  });

  it("clears selected product", () => {
    const onChange = vi.fn();

    render(<LineItemProductPicker value="prod_1" onChange={onChange} />);

    fireEvent.click(screen.getByRole("button", { name: /dissocier/i }));

    expect(onChange).toHaveBeenCalledWith(null);
  });
});

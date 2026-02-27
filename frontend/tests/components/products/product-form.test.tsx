import { beforeEach, describe, expect, it, vi } from "vitest";
import { fireEvent, render, screen, waitFor } from "@testing-library/react";
import { ProductForm } from "@/components/products/product-form";
import { I18nProvider } from "@/components/providers/i18n-provider";

const push = vi.fn();
const back = vi.fn();

vi.mock("next/navigation", () => ({
  useRouter: () => ({ push, back }),
}));

vi.mock("@/lib/stores/products", async () => {
  const actual = await vi.importActual("@/lib/stores/products");
  return {
    ...(actual as object),
    useProductsStore: () => ({
      createProduct: vi.fn(),
      isLoading: false,
    }),
  };
});

function renderWithI18n(component: React.ReactNode) {
  return render(<I18nProvider initialLocale="fr">{component}</I18nProvider>);
}

describe("ProductForm", () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it("submits via onSubmit prop with required data", async () => {
    const onSubmit = vi.fn().mockResolvedValue(undefined);

    renderWithI18n(<ProductForm onSubmit={onSubmit} />);

    fireEvent.change(screen.getByLabelText("Nom du produit"), {
      target: { value: "Formation React" },
    });
    fireEvent.change(screen.getByLabelText("Prix"), {
      target: { value: "499" },
    });

    fireEvent.click(screen.getByRole("button", { name: "Enregistrer" }));

    await waitFor(() => {
      expect(onSubmit).toHaveBeenCalledTimes(1);
      expect(onSubmit).toHaveBeenCalledWith(
        expect.objectContaining({
          name: "Formation React",
          price: 499,
          type: "service",
          price_type: "fixed",
        })
      );
    });
  });

  it("shows validation error on empty name", async () => {
    const onSubmit = vi.fn();

    renderWithI18n(<ProductForm onSubmit={onSubmit} />);

    fireEvent.change(screen.getByLabelText("Nom du produit"), {
      target: { value: "" },
    });

    fireEvent.click(screen.getByRole("button", { name: "Enregistrer" }));

    await waitFor(() => {
      expect(screen.getByText("Le nom est requis")).toBeInTheDocument();
    });
    expect(onSubmit).not.toHaveBeenCalled();
  });

  it("calls router.back on cancel", () => {
    renderWithI18n(<ProductForm onSubmit={vi.fn()} />);

    fireEvent.click(screen.getByRole("button", { name: "Annuler" }));

    expect(back).toHaveBeenCalledTimes(1);
  });
});

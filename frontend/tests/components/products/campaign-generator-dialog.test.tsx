import { describe, expect, it, vi } from "vitest";
import { fireEvent, render, screen } from "@testing-library/react";
import { I18nProvider } from "@/components/providers/i18n-provider";
import { CampaignGeneratorDialog } from "@/components/products/campaign-generator-dialog";

vi.mock("@/components/products/product-campaign-wizard", () => ({
  ProductCampaignWizard: ({ productId }: { productId: string }) => (
    <div data-testid="wizard">Wizard for {productId}</div>
  ),
}));

function renderWithI18n(component: React.ReactNode) {
  return render(<I18nProvider initialLocale="fr">{component}</I18nProvider>);
}

describe("CampaignGeneratorDialog", () => {
  it("opens the dialog and renders the wizard", () => {
    renderWithI18n(
      <CampaignGeneratorDialog
        productId="prod_1"
        triggerLabel="Créer une campagne"
      />
    );

    fireEvent.click(screen.getByRole("button", { name: "Créer une campagne" }));

    expect(screen.getByText("Générer une campagne email IA")).toBeInTheDocument();
    expect(screen.getByTestId("wizard")).toHaveTextContent("Wizard for prod_1");
  });

  it("supports controlled open state", () => {
    renderWithI18n(
      <CampaignGeneratorDialog productId="prod_2" open onOpenChange={vi.fn()} />
    );

    expect(screen.getByTestId("wizard")).toHaveTextContent("Wizard for prod_2");
  });
});

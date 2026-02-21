import { describe, expect, it, vi } from "vitest";
import { render, screen, fireEvent } from "@testing-library/react";
import { DocumentUploadDialog } from "@/components/documents/document-upload-dialog";
import { I18nProvider } from "@/components/providers/i18n-provider";

vi.mock("@/lib/stores/documents", () => ({
  useDocumentStore: () => ({
    uploadDocument: vi.fn(),
    fetchStats: vi.fn(),
  }),
}));

vi.mock("@/lib/stores/clients", () => ({
  useClientStore: () => ({
    clients: [],
  }),
}));

describe("DocumentUploadDialog", () => {
  it("opens dialog when button is clicked", () => {
    render(
      <I18nProvider initialLocale="fr">
        <DocumentUploadDialog />
      </I18nProvider>
    );

    const trigger = screen.getByRole("button", {
      name: /importer un document/i,
    });
    fireEvent.click(trigger);

    expect(screen.getAllByText("Importer un document").length).toBeGreaterThan(
      0
    );
  });

  it("shows file selection zone", () => {
    render(
      <I18nProvider initialLocale="fr">
        <DocumentUploadDialog />
      </I18nProvider>
    );
    fireEvent.click(
      screen.getByRole("button", { name: /importer un document/i })
    );

    expect(
      screen.getByText(/cliquez pour choisir un fichier/i)
    ).toBeInTheDocument();
  });
});

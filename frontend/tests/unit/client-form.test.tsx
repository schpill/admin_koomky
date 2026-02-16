import { render, screen, fireEvent, waitFor } from "@testing-library/react";
import { ClientForm } from "@/components/clients/client-form";
import { vi, expect, test, describe } from "vitest";
import { I18nProvider } from "@/components/providers/i18n-provider";

function renderWithI18n(ui: JSX.Element) {
  return render(<I18nProvider initialLocale="fr">{ui}</I18nProvider>);
}

describe("ClientForm", () => {
  test("renders correctly with initial data", () => {
    const initialData = {
      name: "Acme Corp",
      email: "contact@acme.com",
    };
    renderWithI18n(
      <ClientForm
        initialData={initialData}
        onSubmit={async () => {}}
        onCancel={() => {}}
      />,
    );

    expect(screen.getByLabelText(/Nom entreprise\/client/i)).toHaveValue(
      "Acme Corp",
    );
    expect(screen.getByLabelText(/Email/i)).toHaveValue("contact@acme.com");
  });

  test("calls onSubmit when form is valid", async () => {
    const onSubmit = vi.fn();
    renderWithI18n(<ClientForm onSubmit={onSubmit} onCancel={() => {}} />);

    fireEvent.change(screen.getByLabelText(/Nom entreprise\/client/i), {
      target: { value: "New Client" },
    });

    const submitButton = screen.getByRole("button", {
      name: /Enregistrer le client/i,
    });
    fireEvent.click(submitButton);

    await waitFor(() => {
      expect(onSubmit).toHaveBeenCalledWith(
        expect.objectContaining({
          name: "New Client",
        }),
        expect.anything(),
      );
    });
  });

  test("shows error message when name is too short", async () => {
    renderWithI18n(<ClientForm onSubmit={async () => {}} onCancel={() => {}} />);

    fireEvent.change(screen.getByLabelText(/Nom entreprise\/client/i), {
      target: { value: "A" },
    });
    fireEvent.click(screen.getByText(/Enregistrer le client/i));

    await waitFor(() => {
      expect(
        screen.getByText(/Le nom doit contenir au moins 2 caracteres/i),
      ).toBeInTheDocument();
    });
  });
});

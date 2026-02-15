import { render, screen, fireEvent, waitFor } from "@testing-library/react";
import { ClientForm } from "@/components/clients/client-form";
import { vi, expect, test, describe } from "vitest";

describe("ClientForm", () => {
  test("renders correctly with initial data", () => {
    const initialData = {
      name: "Acme Corp",
      email: "contact@acme.com",
    };
    render(
      <ClientForm
        initialData={initialData}
        onSubmit={async () => {}}
        onCancel={() => {}}
      />,
    );

    expect(screen.getByLabelText(/Company\/Client Name/i)).toHaveValue(
      "Acme Corp",
    );
    expect(screen.getByLabelText(/Email/i)).toHaveValue("contact@acme.com");
  });

  test("calls onSubmit when form is valid", async () => {
    const onSubmit = vi.fn();
    render(<ClientForm onSubmit={onSubmit} onCancel={() => {}} />);

    fireEvent.change(screen.getByLabelText(/Company\/Client Name/i), {
      target: { value: "New Client" },
    });

    const submitButton = screen.getByRole("button", { name: /Save Client/i });
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
    render(<ClientForm onSubmit={async () => {}} onCancel={() => {}} />);

    fireEvent.change(screen.getByLabelText(/Company\/Client Name/i), {
      target: { value: "A" },
    });
    fireEvent.click(screen.getByText(/Save Client/i));

    await waitFor(() => {
      expect(
        screen.getByText(/Name must be at least 2 characters/i),
      ).toBeInTheDocument();
    });
  });
});

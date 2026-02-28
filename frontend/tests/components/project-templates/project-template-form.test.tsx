import { fireEvent, render, screen, waitFor } from "@testing-library/react";
import { describe, expect, it, vi } from "vitest";
import { ProjectTemplateForm } from "@/components/project-templates/project-template-form";

describe("ProjectTemplateForm", () => {
  it("submits normalized template data", async () => {
    const onSubmit = vi.fn().mockResolvedValue(undefined);

    render(
      <ProjectTemplateForm
        submitLabel="Créer"
        onSubmit={onSubmit}
        defaultValues={{
          name: "",
          description: "",
          billing_type: "hourly",
          default_hourly_rate: 120,
          default_currency: "EUR",
          estimated_hours: 16,
          tasks: [],
        }}
      />
    );

    fireEvent.change(screen.getByLabelText(/nom du template/i), {
      target: { value: "Website template" },
    });

    fireEvent.click(screen.getByRole("button", { name: "Créer" }));

    await waitFor(() => {
      expect(onSubmit).toHaveBeenCalledWith(
        expect.objectContaining({
          name: "Website template",
          billing_type: "hourly",
          default_currency: "EUR",
        })
      );
    });
  });
});

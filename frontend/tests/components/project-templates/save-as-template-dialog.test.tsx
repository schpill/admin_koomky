import { fireEvent, render, screen, waitFor } from "@testing-library/react";
import { describe, expect, it, vi } from "vitest";
import { SaveAsTemplateDialog } from "@/components/project-templates/save-as-template-dialog";

const saveProjectAsTemplate = vi.fn().mockResolvedValue({ id: "template-1" });

vi.mock("@/lib/stores/project-templates", () => ({
  useProjectTemplatesStore: () => ({
    saveProjectAsTemplate,
  }),
}));

vi.mock("sonner", () => ({
  toast: {
    success: vi.fn(),
    error: vi.fn(),
  },
}));

vi.mock("@/components/providers/i18n-provider", () => ({
  useI18n: () => ({
    t: (key: string) => key,
  }),
}));

describe("SaveAsTemplateDialog", () => {
  it("submits the project snapshot as a template", async () => {
    const onOpenChange = vi.fn();

    render(
      <SaveAsTemplateDialog
        open
        onOpenChange={onOpenChange}
        projectId="project-1"
        projectName="Current project"
      />
    );

    fireEvent.change(screen.getByLabelText(/nom du template/i), {
      target: { value: "Delivery template" },
    });

    fireEvent.click(
      screen.getByRole("button", { name: /sauvegarder le template/i })
    );

    await waitFor(() => {
      expect(saveProjectAsTemplate).toHaveBeenCalledWith(
        "project-1",
        "Delivery template",
        ""
      );
      expect(onOpenChange).toHaveBeenCalledWith(false);
    });
  });
});

import { fireEvent, render, screen, waitFor } from "@testing-library/react";
import { beforeEach, describe, expect, it, vi } from "vitest";
import { InstantiateTemplateDialog } from "@/components/project-templates/instantiate-template-dialog";

const instantiateTemplate = vi.fn().mockResolvedValue({ id: "project-1" });
const fetchClients = vi.fn();

vi.mock("@/lib/stores/project-templates", () => ({
  useProjectTemplatesStore: () => ({
    instantiateTemplate,
    templates: [
      {
        id: "template-1",
        name: "Website template",
        tasks: [{ id: "task-1", title: "Kickoff" }],
      },
    ],
  }),
}));

vi.mock("@/lib/stores/clients", () => ({
  useClientStore: () => ({
    clients: [{ id: "client-1", name: "Acme" }],
    fetchClients,
  }),
}));

vi.mock("next/navigation", () => ({
  useRouter: () => ({
    push: vi.fn(),
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

describe("InstantiateTemplateDialog", () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it("loads clients and submits instantiate payload", async () => {
    const onSuccess = vi.fn();

    render(
      <InstantiateTemplateDialog
        templateId="template-1"
        open
        onOpenChange={vi.fn()}
        onSuccess={onSuccess}
      />
    );

    expect(fetchClients).toHaveBeenCalled();

    fireEvent.change(screen.getByLabelText(/nom du projet/i), {
      target: { value: "Client rollout" },
    });
    fireEvent.change(screen.getByLabelText(/client/i), {
      target: { value: "client-1" },
    });

    fireEvent.click(screen.getByRole("button", { name: /créer le projet/i }));

    await waitFor(() => {
      expect(instantiateTemplate).toHaveBeenCalledWith("template-1", {
        name: "Client rollout",
        client_id: "client-1",
        start_date: "",
        deadline: "",
      });
      expect(onSuccess).toHaveBeenCalledWith("project-1");
    });
  });
});

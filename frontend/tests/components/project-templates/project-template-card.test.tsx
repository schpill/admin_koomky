import { fireEvent, render, screen, waitFor } from "@testing-library/react";
import { beforeEach, describe, expect, it, vi } from "vitest";
import { ProjectTemplateCard } from "@/components/project-templates/project-template-card";

const routerPush = vi.fn();
const storeMock = {
  deleteTemplate: vi.fn().mockResolvedValue(undefined),
  duplicateTemplate: vi.fn().mockResolvedValue(undefined),
};

vi.mock("next/navigation", () => ({
  useRouter: () => ({
    push: routerPush,
  }),
}));

vi.mock("@/lib/stores/project-templates", () => ({
  useProjectTemplatesStore: () => storeMock,
}));

vi.mock("sonner", () => ({
  toast: {
    success: vi.fn(),
    error: vi.fn(),
  },
}));

describe("ProjectTemplateCard", () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it("renders template metadata and triggers instantiate", () => {
    const onInstantiate = vi.fn();

    render(
      <ProjectTemplateCard
        template={{
          id: "template-1",
          name: "Website template",
          description: "Reusable client project",
          billing_type: "hourly",
          tasks_count: 3,
        }}
        onInstantiate={onInstantiate}
      />
    );

    expect(screen.getByText("Website template")).toBeInTheDocument();
    expect(screen.getByText("3 tâches")).toBeInTheDocument();

    fireEvent.click(screen.getByRole("button", { name: /utiliser/i }));

    expect(onInstantiate).toHaveBeenCalledWith("template-1");
  });

  it("navigates to the detail page from the actions menu", async () => {
    render(
      <ProjectTemplateCard
        template={{
          id: "template-1",
          name: "Website template",
          description: null,
          billing_type: "hourly",
          tasks_count: 3,
        }}
      />
    );

    fireEvent.click(screen.getByRole("button", { name: "Website template" }));

    await waitFor(() => {
      expect(routerPush).toHaveBeenCalledWith(
        "/settings/project-templates/template-1"
      );
    });
  });
});

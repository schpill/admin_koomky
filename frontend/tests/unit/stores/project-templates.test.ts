import { beforeEach, describe, expect, it, vi } from "vitest";
import { useProjectTemplatesStore } from "@/lib/stores/project-templates";

vi.mock("@/lib/api", () => ({
  apiClient: {
    get: vi.fn(),
    post: vi.fn(),
    patch: vi.fn(),
    delete: vi.fn(),
  },
}));

import { apiClient } from "@/lib/api";

describe("useProjectTemplatesStore", () => {
  beforeEach(() => {
    useProjectTemplatesStore.setState({
      templates: [],
      selectedTemplate: null,
      isLoading: false,
      error: null,
    });
    vi.clearAllMocks();
  });

  it("fetches templates from the API payload", async () => {
    (apiClient.get as any).mockResolvedValue({
      data: [
        {
          id: "template-1",
          name: "Website template",
          description: null,
          billing_type: "hourly",
          default_hourly_rate: 120,
          default_currency: "EUR",
          estimated_hours: 24,
          tasks_count: 2,
          created_at: "2026-02-28T10:00:00Z",
          tasks: [],
        },
      ],
    });
    await useProjectTemplatesStore.getState().fetchTemplates();

    expect(useProjectTemplatesStore.getState().templates).toHaveLength(1);
    expect(useProjectTemplatesStore.getState().templates[0].name).toBe(
      "Website template"
    );
  });

  it("falls back to an empty template list for malformed payloads", async () => {
    (apiClient.get as any).mockResolvedValue({
      data: {},
    });

    await useProjectTemplatesStore.getState().fetchTemplates();

    expect(useProjectTemplatesStore.getState().templates).toEqual([]);
    expect(useProjectTemplatesStore.getState().error).toBeNull();
  });

  it("reads the paginated backend template payload", async () => {
    (apiClient.get as any).mockResolvedValue({
      data: {
        data: [
          {
            id: "template-2",
            name: "Retainer template",
            description: null,
            billing_type: "fixed",
            default_hourly_rate: null,
            default_currency: "EUR",
            estimated_hours: 10,
            tasks_count: 1,
            created_at: "2026-02-28T10:00:00Z",
            tasks: [],
          },
        ],
        current_page: 1,
        last_page: 1,
        per_page: 15,
        total: 1,
      },
    });

    await useProjectTemplatesStore.getState().fetchTemplates();

    expect(useProjectTemplatesStore.getState().templates).toHaveLength(1);
    expect(useProjectTemplatesStore.getState().templates[0].id).toBe(
      "template-2"
    );
  });

  it("saves a project as template and prepends it to state", async () => {
    (apiClient.post as any).mockResolvedValue({
      data: {
        id: "template-1",
        name: "Saved from project",
        description: "Snapshot",
        billing_type: "hourly",
        default_hourly_rate: 120,
        default_currency: "EUR",
        estimated_hours: 24,
        tasks_count: 2,
        created_at: "2026-02-28T10:00:00Z",
        tasks: [],
      },
    });

    const template = await useProjectTemplatesStore
      .getState()
      .saveProjectAsTemplate("project-1", "Saved from project", "Snapshot");

    expect(template.id).toBe("template-1");
    expect(useProjectTemplatesStore.getState().templates[0].id).toBe(
      "template-1"
    );
  });
});

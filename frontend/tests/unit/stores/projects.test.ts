import { describe, it, expect, beforeEach, vi } from "vitest";
import { useProjectStore } from "@/lib/stores/projects";

vi.mock("@/lib/api", () => ({
  apiClient: {
    get: vi.fn(),
    post: vi.fn(),
    put: vi.fn(),
    delete: vi.fn(),
  },
}));

import { apiClient } from "@/lib/api";

describe("useProjectStore", () => {
  beforeEach(() => {
    useProjectStore.setState({
      projects: [],
      tasks: [],
      currentProject: null,
      pagination: null,
      isLoading: false,
      error: null,
    });
    vi.clearAllMocks();
  });

  it("fetches projects and updates state", async () => {
    (apiClient.get as any).mockResolvedValue({
      data: {
        data: [{ id: "p1", name: "Site Redesign" }],
        current_page: 1,
        last_page: 1,
        per_page: 15,
        total: 1,
      },
    });

    await useProjectStore.getState().fetchProjects();

    const state = useProjectStore.getState();
    expect(state.projects).toHaveLength(1);
    expect(state.projects[0].name).toBe("Site Redesign");
    expect(state.pagination?.total).toBe(1);
  });

  it("creates project and prepends to list", async () => {
    (apiClient.post as any).mockResolvedValue({
      data: { id: "p2", name: "New Project", status: "draft" },
    });

    await useProjectStore.getState().createProject({ name: "New Project" });

    const state = useProjectStore.getState();
    expect(state.projects[0].id).toBe("p2");
  });

  it("updates and deletes project", async () => {
    useProjectStore.setState({
      projects: [
        { id: "p1", name: "Old Name", status: "draft" } as any,
        { id: "p2", name: "Keep", status: "draft" } as any,
      ],
      currentProject: { id: "p1", name: "Old Name", status: "draft" } as any,
    });

    (apiClient.put as any).mockResolvedValue({
      data: { id: "p1", name: "New Name", status: "proposal_sent" },
    });

    await useProjectStore.getState().updateProject("p1", {
      name: "New Name",
      status: "proposal_sent",
    });

    expect(useProjectStore.getState().projects[0].name).toBe("New Name");
    expect(useProjectStore.getState().currentProject?.status).toBe(
      "proposal_sent"
    );

    (apiClient.delete as any).mockResolvedValue({});

    await useProjectStore.getState().deleteProject("p1");

    expect(useProjectStore.getState().projects).toHaveLength(1);
    expect(useProjectStore.getState().projects[0].id).toBe("p2");
  });

  it("fetches project tasks and handles task operations", async () => {
    (apiClient.get as any).mockResolvedValueOnce({
      data: {
        id: "p1",
        name: "Project",
      },
    });

    (apiClient.get as any).mockResolvedValueOnce({
      data: [{ id: "t1", title: "Task 1", status: "todo", priority: "medium" }],
    });

    await useProjectStore.getState().fetchProject("p1");

    expect(useProjectStore.getState().tasks).toHaveLength(1);

    (apiClient.post as any).mockResolvedValueOnce({
      data: { id: "t2", title: "Task 2", status: "todo", priority: "high" },
    });

    await useProjectStore.getState().createTask("p1", {
      title: "Task 2",
      priority: "high",
    });

    expect(useProjectStore.getState().tasks).toHaveLength(2);

    (apiClient.put as any).mockResolvedValueOnce({
      data: {
        id: "t1",
        title: "Task 1 updated",
        status: "in_progress",
        priority: "medium",
      },
    });

    await useProjectStore.getState().updateTask("p1", "t1", {
      title: "Task 1 updated",
      status: "in_progress",
      priority: "medium",
    });

    expect(
      useProjectStore.getState().tasks.find((task) => task.id === "t1")?.status
    ).toBe("in_progress");

    (apiClient.post as any).mockResolvedValueOnce({
      data: null,
    });

    await useProjectStore.getState().reorderTasks("p1", ["t2", "t1"]);

    expect(useProjectStore.getState().tasks[0].id).toBe("t2");

    (apiClient.delete as any).mockResolvedValueOnce({});

    await useProjectStore.getState().deleteTask("p1", "t2");

    expect(useProjectStore.getState().tasks).toHaveLength(1);
  });

  it("records store error when API action fails", async () => {
    (apiClient.get as any).mockRejectedValue(new Error("Fetch failed"));

    await useProjectStore.getState().fetchProjects();

    expect(useProjectStore.getState().error).toBe("Fetch failed");
  });
});

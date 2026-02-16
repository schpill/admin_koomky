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
  const baseProject = {
    id: "p1",
    name: "Project",
    status: "draft",
  };

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

  it("handles empty project list payload", async () => {
    (apiClient.get as any).mockResolvedValue({
      data: {
        current_page: 1,
        last_page: 1,
        per_page: 15,
        total: 0,
      },
    });

    await useProjectStore.getState().fetchProjects();

    expect(useProjectStore.getState().projects).toEqual([]);
    expect(useProjectStore.getState().pagination?.total).toBe(0);
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

  it("handles project fetch mapping, fallbacks and fetch errors", async () => {
    (apiClient.get as any).mockResolvedValueOnce({
      data: {
        ...baseProject,
      },
    });
    (apiClient.get as any).mockResolvedValueOnce({
      data: [
        {
          id: "t1",
          title: "Done dependency",
          status: "todo",
          priority: "medium",
          dependencies: [{ status: "done" }],
        },
        {
          id: "t2",
          title: "Blocked dependency",
          status: "todo",
          priority: "high",
          dependencies: [{ status: "in_progress" }],
        },
      ],
    });

    await useProjectStore.getState().fetchProject("p1");

    expect(
      useProjectStore.getState().tasks.find((task) => task.id === "t1")
        ?.blocked_by_dependencies
    ).toBe(false);
    expect(
      useProjectStore.getState().tasks.find((task) => task.id === "t2")
        ?.blocked_by_dependencies
    ).toBe(true);

    (apiClient.get as any).mockResolvedValueOnce({
      data: {
        id: "p2",
        name: "Project 2",
      },
    });
    (apiClient.get as any).mockResolvedValueOnce({
      data: null,
    });

    await useProjectStore.getState().fetchProject("p2");
    expect(useProjectStore.getState().tasks).toEqual([]);

    (apiClient.get as any).mockRejectedValueOnce(new Error("Project failed"));
    await expect(
      useProjectStore.getState().fetchProject("p404")
    ).rejects.toThrow("Project failed");
  });

  it("covers project CRUD alternate branches and failures", async () => {
    useProjectStore.setState({
      projects: [
        { id: "p1", name: "A", status: "draft" } as any,
        { id: "p2", name: "B", status: "in_progress" } as any,
      ],
      currentProject: { id: "p2", name: "B", status: "in_progress" } as any,
      tasks: [{ id: "t1", title: "Task 1" } as any],
      pagination: null,
      isLoading: false,
      error: null,
    });

    (apiClient.put as any).mockResolvedValueOnce({
      data: { id: "p1", name: "A updated", status: "proposal_sent" },
    });
    await useProjectStore.getState().updateProject("p1", {
      name: "A updated",
      status: "proposal_sent",
    });
    expect(useProjectStore.getState().currentProject?.id).toBe("p2");

    (apiClient.delete as any).mockResolvedValueOnce({});
    await useProjectStore.getState().deleteProject("p1");
    expect(useProjectStore.getState().currentProject?.id).toBe("p2");
    expect(useProjectStore.getState().tasks).toHaveLength(1);

    (apiClient.post as any).mockRejectedValueOnce(new Error("Create failed"));
    await expect(
      useProjectStore.getState().createProject({ name: "Failing project" })
    ).rejects.toThrow("Create failed");

    (apiClient.put as any).mockRejectedValueOnce(new Error("Update failed"));
    await expect(
      useProjectStore.getState().updateProject("p2", { name: "Nope" })
    ).rejects.toThrow("Update failed");

    (apiClient.delete as any).mockRejectedValueOnce(new Error("Delete failed"));
    await expect(
      useProjectStore.getState().deleteProject("p2")
    ).rejects.toThrow("Delete failed");
  });

  it("covers task/dependency/time-entry failure branches", async () => {
    useProjectStore.setState({
      projects: [],
      currentProject: null,
      tasks: [
        { id: "t1", title: "T1", status: "todo", priority: "medium" } as any,
        { id: "t2", title: "T2", status: "todo", priority: "high" } as any,
      ],
      pagination: null,
      isLoading: false,
      error: null,
    });

    (apiClient.post as any).mockRejectedValueOnce(
      new Error("Create task failed")
    );
    await expect(
      useProjectStore.getState().createTask("p1", { title: "x" })
    ).rejects.toThrow("Create task failed");

    (apiClient.put as any).mockRejectedValueOnce(
      new Error("Update task failed")
    );
    await expect(
      useProjectStore.getState().updateTask("p1", "t1", { title: "x" })
    ).rejects.toThrow("Update task failed");

    (apiClient.delete as any).mockRejectedValueOnce(
      new Error("Delete task failed")
    );
    await expect(
      useProjectStore.getState().deleteTask("p1", "t1")
    ).rejects.toThrow("Delete task failed");

    (apiClient.post as any).mockRejectedValueOnce(
      new Error("Reorder task failed")
    );
    await expect(
      useProjectStore.getState().reorderTasks("p1", ["t2", "t1"])
    ).rejects.toThrow("Reorder task failed");

    (apiClient.post as any).mockRejectedValueOnce(
      new Error("Dependency failed")
    );
    await expect(
      useProjectStore.getState().addTaskDependency("p1", "t1", "t2")
    ).rejects.toThrow("Dependency failed");

    (apiClient.post as any).mockRejectedValueOnce(
      new Error("Time entry failed")
    );
    await expect(
      useProjectStore.getState().createTimeEntry("p1", "t1", {
        duration_minutes: 30,
        date: "2026-02-16",
      })
    ).rejects.toThrow("Time entry failed");
  });
});

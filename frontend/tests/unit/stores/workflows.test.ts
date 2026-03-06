import { beforeEach, describe, expect, it, vi } from "vitest";
import { useWorkflowStore } from "@/lib/stores/workflows";

vi.mock("@/lib/api", () => ({
  apiClient: {
    get: vi.fn(),
    post: vi.fn(),
    put: vi.fn(),
    delete: vi.fn(),
    patch: vi.fn(),
  },
}));

import { apiClient } from "@/lib/api";

describe("useWorkflowStore", () => {
  beforeEach(() => {
    useWorkflowStore.setState({
      workflows: [],
      currentWorkflow: null,
      enrollments: [],
      isLoading: false,
      error: null,
    } as any);
    vi.clearAllMocks();
  });

  it("fetches and creates workflows", async () => {
    (apiClient.get as any).mockResolvedValueOnce({
      data: [
        {
          id: "wf_1",
          name: "Welcome",
          trigger_type: "manual",
          status: "draft",
          steps: [],
          enrollments: [],
          analytics: { active_enrollments: 0, completion_rate: 0, dropoff_by_step: [] },
        },
      ],
    });

    await useWorkflowStore.getState().fetchWorkflows();
    expect(useWorkflowStore.getState().workflows).toHaveLength(1);

    (apiClient.post as any).mockResolvedValueOnce({
      data: {
        id: "wf_2",
        name: "Qualified leads",
        trigger_type: "score_threshold",
        status: "draft",
        steps: [],
        enrollments: [],
        analytics: { active_enrollments: 0, completion_rate: 0, dropoff_by_step: [] },
      },
    });

    const created = await useWorkflowStore.getState().createWorkflow({
      name: "Qualified leads",
    });

    expect(created?.id).toBe("wf_2");
  });

  it("updates workflow state and enrollment actions", async () => {
    useWorkflowStore.setState({
      workflows: [
        {
          id: "wf_1",
          name: "Welcome",
          trigger_type: "manual",
          status: "draft",
          steps: [],
          enrollments: [],
          analytics: { active_enrollments: 0, completion_rate: 0, dropoff_by_step: [] },
        },
      ],
      currentWorkflow: null,
      enrollments: [],
      isLoading: false,
      error: null,
    } as any);

    (apiClient.put as any).mockResolvedValueOnce({
      data: {
        id: "wf_1",
        name: "Welcome v2",
        trigger_type: "manual",
        status: "paused",
        steps: [],
        enrollments: [],
        analytics: { active_enrollments: 0, completion_rate: 0, dropoff_by_step: [] },
      },
    });
    const updated = await useWorkflowStore
      .getState()
      .updateWorkflow("wf_1", { name: "Welcome v2" });
    expect(updated?.name).toBe("Welcome v2");

    (apiClient.patch as any)
      .mockResolvedValueOnce({ data: { id: "enr_1", status: "paused" } })
      .mockResolvedValueOnce({ data: { id: "enr_1", status: "active" } })
      .mockResolvedValueOnce({ data: { id: "enr_1", status: "cancelled" } });

    await useWorkflowStore.getState().pauseEnrollment("enr_1");
    await useWorkflowStore.getState().resumeEnrollment("enr_1");
    await useWorkflowStore.getState().cancelEnrollment("enr_1");

    (apiClient.delete as any).mockResolvedValueOnce({});
    await useWorkflowStore.getState().deleteWorkflow("wf_1");
    expect(useWorkflowStore.getState().workflows).toHaveLength(0);
  });
});

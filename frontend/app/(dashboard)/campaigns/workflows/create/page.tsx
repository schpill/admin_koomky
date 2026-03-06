"use client";

import { useState } from "react";
import { useRouter } from "next/navigation";
import { WorkflowBuilder } from "@/components/workflows/workflow-builder";
import { normalizeWorkflowStepWithMap } from "@/components/workflows/workflow-step-persistence";
import { useWorkflowsStore, type Workflow } from "@/lib/stores/workflows";

export default function CreateWorkflowPage() {
  const router = useRouter();
  const { createWorkflow, createStep, updateStep, updateWorkflow } =
    useWorkflowsStore();
  const [workflow, setWorkflow] = useState<Workflow>({
    id: "draft",
    name: "New workflow",
    description: "",
    trigger_type: "manual",
    trigger_config: {},
    status: "draft",
    entry_step_id: null,
    steps: [],
    enrollments: [],
    active_enrollments_count: 0,
    completion_rate: 0,
  });

  const save = async () => {
    const created = await createWorkflow({
      name: workflow.name,
      description: workflow.description,
      trigger_type: workflow.trigger_type,
      trigger_config: workflow.trigger_config,
      status: workflow.status,
    });

    if (!created?.id) {
      return;
    }

    const idMap = new Map<string, string>();

    for (const step of workflow.steps) {
      const createdStep = await createStep(
        created.id,
        normalizeWorkflowStepWithMap(step, idMap)
      );
      if (step.id && createdStep?.id) {
        idMap.set(step.id, createdStep.id);
      }
    }

    for (const step of workflow.steps) {
      const createdStepId = step.id ? idMap.get(step.id) : null;
      if (!createdStepId) continue;
      await updateStep(
        createdStepId,
        normalizeWorkflowStepWithMap(step, idMap)
      );
    }

    await updateWorkflow(created.id, {
      name: workflow.name,
      description: workflow.description,
      trigger_type: workflow.trigger_type,
      trigger_config: workflow.trigger_config,
      status: workflow.status,
      entry_step_id: workflow.entry_step_id
        ? idMap.get(workflow.entry_step_id) || null
        : null,
    });

    router.push(`/campaigns/workflows/${created.id}`);
  };

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-3xl font-bold">Create workflow</h1>
        <p className="text-sm text-muted-foreground">
          Configure the trigger, then assemble nodes into an automation flow.
        </p>
      </div>

      <WorkflowBuilder value={workflow} onChange={setWorkflow} onSave={save} />
    </div>
  );
}

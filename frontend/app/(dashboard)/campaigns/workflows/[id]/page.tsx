"use client";

import { useEffect, useState } from "react";
import { useParams } from "next/navigation";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { WorkflowBuilder } from "@/components/workflows/workflow-builder";
import { WorkflowEnrollmentsTable } from "@/components/workflows/workflow-enrollments-table";
import {
  normalizeWorkflowStep,
  normalizeWorkflowStepWithMap,
} from "@/components/workflows/workflow-step-persistence";
import { useWorkflowsStore, type Workflow } from "@/lib/stores/workflows";

function cloneWorkflow(workflow: Workflow): Workflow {
  return {
    ...workflow,
    trigger_config: { ...(workflow.trigger_config || {}) },
    steps: workflow.steps.map((step) => ({
      ...step,
      config: { ...(step.config || {}) },
    })),
    enrollments: [...workflow.enrollments],
  };
}

export default function WorkflowDetailPage() {
  const params = useParams<{ id: string }>();
  const {
    currentWorkflow,
    enrollments,
    fetchWorkflow,
    fetchEnrollments,
    updateWorkflow,
    createStep,
    updateStep,
    deleteStep,
    activateWorkflow,
    pauseWorkflow,
    pauseEnrollment,
    resumeEnrollment,
    cancelEnrollment,
  } = useWorkflowsStore();
  const [draft, setDraft] = useState<Workflow | null>(null);

  useEffect(() => {
    if (!params?.id) return;

    fetchWorkflow(params.id).catch(() => undefined);
    fetchEnrollments(params.id).catch(() => undefined);
  }, [fetchEnrollments, fetchWorkflow, params?.id]);

  useEffect(() => {
    if (currentWorkflow) {
      setDraft(cloneWorkflow(currentWorkflow));
    }
  }, [currentWorkflow]);

  const save = async () => {
    if (!draft || !currentWorkflow) return;

    await updateWorkflow(currentWorkflow.id, {
      name: draft.name,
      description: draft.description,
      trigger_type: draft.trigger_type,
      trigger_config: draft.trigger_config,
      status: draft.status,
      entry_step_id: draft.entry_step_id || null,
    });

    const existingIds = new Set(
      currentWorkflow.steps
        .map((step) => step.id)
        .filter((stepId): stepId is string => Boolean(stepId))
    );
    const draftIds = new Set(
      draft.steps.map((step) => step.id).filter(Boolean) as string[]
    );
    const idMap = new Map<string, string>();

    for (const step of draft.steps) {
      if (!step.id || String(step.id).startsWith("tmp-")) {
        const createdStep = await createStep(
          currentWorkflow.id,
          normalizeWorkflowStepWithMap(step, idMap)
        );
        if (step.id && createdStep?.id) {
          idMap.set(step.id, createdStep.id);
        }
      } else {
        await updateStep(step.id, normalizeWorkflowStep(step));
      }
    }

    for (const step of draft.steps) {
      const resolvedStepId = step.id ? idMap.get(step.id) || step.id : null;
      if (!resolvedStepId) {
        continue;
      }

      await updateStep(
        resolvedStepId,
        normalizeWorkflowStepWithMap(step, idMap)
      );
    }

    for (const stepId of existingIds) {
      if (!draftIds.has(stepId)) {
        await deleteStep(stepId);
      }
    }

    await fetchWorkflow(currentWorkflow.id);
  };

  if (!draft) {
    return <p className="text-sm text-muted-foreground">Loading workflow...</p>;
  }

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between gap-3">
        <div>
          <h1 className="text-3xl font-bold">{draft.name || "Workflow"}</h1>
          <p className="text-sm text-muted-foreground">
            Review nodes, status and active enrollments.
          </p>
        </div>
        <div className="flex gap-2">
          <Button
            type="button"
            variant="outline"
            onClick={() =>
              void (draft.status === "active"
                ? pauseWorkflow(draft.id)
                : activateWorkflow(draft.id))
            }
          >
            {draft.status === "active" ? "Pause" : "Activate"}
          </Button>
        </div>
      </div>

      <WorkflowBuilder value={draft} onChange={setDraft} onSave={save} />

      <Card>
        <CardHeader>
          <CardTitle>Enrollments</CardTitle>
        </CardHeader>
        <CardContent>
          <WorkflowEnrollmentsTable
            enrollments={enrollments}
            onPause={(id) => void pauseEnrollment(id)}
            onResume={(id) => void resumeEnrollment(id)}
            onCancel={(id) => void cancelEnrollment(id)}
          />
        </CardContent>
      </Card>
    </div>
  );
}

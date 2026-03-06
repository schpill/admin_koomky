"use client";

import { useEffect, useState } from "react";
import { useParams } from "next/navigation";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { WorkflowBuilder } from "@/components/workflows/workflow-builder";
import { WorkflowEnrollmentsTable } from "@/components/workflows/workflow-enrollments-table";
import { useWorkflowStore, type WorkflowStep } from "@/lib/stores/workflows";

export default function WorkflowDetailPage() {
  const params = useParams<{ id: string }>();
  const {
    currentWorkflow,
    enrollments,
    fetchWorkflow,
    fetchEnrollments,
    updateWorkflow,
    saveWorkflowGraph,
    activateWorkflow,
    pauseWorkflow,
    pauseEnrollment,
    resumeEnrollment,
    cancelEnrollment,
  } = useWorkflowStore();
  const [name, setName] = useState("");
  const [description, setDescription] = useState("");
  const [triggerConfig, setTriggerConfig] = useState("{}");
  const [steps, setSteps] = useState<WorkflowStep[]>([]);
  const [entryStepId, setEntryStepId] = useState<string | null>(null);

  useEffect(() => {
    if (!params?.id) {
      return;
    }

    fetchWorkflow(params.id)
      .then((workflow) => {
        if (!workflow) return;
        setName(workflow.name);
        setDescription(workflow.description || "");
        setTriggerConfig(JSON.stringify(workflow.trigger_config || {}, null, 2));
        setSteps(workflow.steps || []);
        setEntryStepId(workflow.entry_step_id || workflow.steps[0]?.id || null);
      })
      .catch(() => undefined);

    fetchEnrollments(params.id).catch(() => undefined);
  }, [fetchEnrollments, fetchWorkflow, params?.id]);

  const save = async () => {
    if (!params?.id) return;

    await updateWorkflow(params.id, {
      name,
      description,
      trigger_config: JSON.parse(triggerConfig || "{}"),
    });
    await saveWorkflowGraph(params.id, steps, entryStepId);
  };

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between gap-3">
        <div>
          <h1 className="text-3xl font-bold">
            {currentWorkflow?.name || "Workflow"}
          </h1>
          <p className="text-sm text-muted-foreground">
            Trigger: {currentWorkflow?.trigger_type || "manual"} • Completion:{" "}
            {Number(currentWorkflow?.analytics?.completion_rate || 0).toFixed(2)}%
          </p>
        </div>
        <div className="flex gap-2">
          {currentWorkflow?.status === "active" ? (
            <Button type="button" variant="outline" onClick={() => void pauseWorkflow(params.id)}>
              Pause
            </Button>
          ) : (
            <Button type="button" variant="outline" onClick={() => void activateWorkflow(params.id)}>
              Activate
            </Button>
          )}
          <Button type="button" onClick={() => void save()}>
            Save changes
          </Button>
        </div>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Workflow settings</CardTitle>
        </CardHeader>
        <CardContent className="grid gap-4 md:grid-cols-2">
          <div className="space-y-2">
            <Label htmlFor="workflow-name">Name</Label>
            <Input
              id="workflow-name"
              value={name}
              onChange={(event) => setName(event.target.value)}
            />
          </div>
          <div className="space-y-2">
            <Label htmlFor="workflow-status">Status</Label>
            <Input
              id="workflow-status"
              value={currentWorkflow?.status || ""}
              readOnly
            />
          </div>
          <div className="space-y-2 md:col-span-2">
            <Label htmlFor="workflow-description">Description</Label>
            <Textarea
              id="workflow-description"
              value={description}
              rows={3}
              onChange={(event) => setDescription(event.target.value)}
            />
          </div>
          <div className="space-y-2 md:col-span-2">
            <Label htmlFor="workflow-trigger-config">Trigger config JSON</Label>
            <Textarea
              id="workflow-trigger-config"
              value={triggerConfig}
              rows={4}
              onChange={(event) => setTriggerConfig(event.target.value)}
            />
          </div>
        </CardContent>
      </Card>

      <WorkflowBuilder
        value={steps}
        entryStepId={entryStepId}
        onChange={(nextSteps, nextEntryStepId) => {
          setSteps(nextSteps);
          setEntryStepId(nextEntryStepId || null);
        }}
      />

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

"use client";

import { useState } from "react";
import { useRouter } from "next/navigation";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { WorkflowBuilder } from "@/components/workflows/workflow-builder";
import { useWorkflowStore, type WorkflowStep } from "@/lib/stores/workflows";

const initialSteps: WorkflowStep[] = [
  {
    id: "local-send",
    type: "send_email",
    config: {
      subject: "Welcome",
      content: "<p>Hello {{first_name}}</p>",
    },
    next_step_id: "local-end",
    else_step_id: null,
    position_x: 0,
    position_y: 0,
  },
  {
    id: "local-end",
    type: "end",
    config: {},
    next_step_id: null,
    else_step_id: null,
    position_x: 240,
    position_y: 0,
  },
];

export default function CreateWorkflowPage() {
  const router = useRouter();
  const { createWorkflow, saveWorkflowGraph } = useWorkflowStore();
  const [name, setName] = useState("");
  const [description, setDescription] = useState("");
  const [triggerType, setTriggerType] = useState<
    | "manual"
    | "contact_created"
    | "email_opened"
    | "email_clicked"
    | "score_threshold"
    | "segment_entered"
  >("manual");
  const [triggerConfig, setTriggerConfig] = useState("{}");
  const [steps, setSteps] = useState<WorkflowStep[]>(initialSteps);
  const [entryStepId, setEntryStepId] = useState<string | null>("local-send");

  const save = async () => {
    const workflow = await createWorkflow({
      name,
      description,
      trigger_type: triggerType,
      trigger_config: JSON.parse(triggerConfig || "{}"),
      status: "draft",
    });

    if (!workflow?.id) {
      return;
    }

    await saveWorkflowGraph(workflow.id, steps, entryStepId);
    router.push(`/campaigns/workflows/${workflow.id}`);
  };

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-3xl font-bold">Create workflow</h1>
        <p className="text-sm text-muted-foreground">
          Configure trigger and graph, then save the automation.
        </p>
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
            <Label htmlFor="workflow-trigger-type">Trigger</Label>
            <select
              id="workflow-trigger-type"
              className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
              value={triggerType}
              onChange={(event) =>
                setTriggerType(event.target.value as typeof triggerType)
              }
            >
              <option value="manual">Manual</option>
              <option value="contact_created">Contact created</option>
              <option value="email_opened">Email opened</option>
              <option value="email_clicked">Email clicked</option>
              <option value="score_threshold">Score threshold</option>
              <option value="segment_entered">Segment entered</option>
            </select>
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

      <Button type="button" onClick={() => void save()}>
        Save workflow
      </Button>
    </div>
  );
}

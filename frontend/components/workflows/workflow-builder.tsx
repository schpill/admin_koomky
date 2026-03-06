"use client";

import { useMemo, useState } from "react";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import {
  type Workflow,
  type WorkflowStep,
  type WorkflowStepType,
} from "@/lib/stores/workflows";
import { WorkflowNodeConfig } from "@/components/workflows/workflow-node-config";

interface WorkflowBuilderProps {
  value: Workflow;
  onChange: (next: Workflow) => void;
  onSave: () => Promise<void>;
}

const STEP_TYPES: WorkflowStepType[] = [
  "send_email",
  "wait",
  "condition",
  "update_score",
  "add_tag",
  "end",
];

function createStep(type: WorkflowStepType): WorkflowStep {
  return {
    id: `tmp-${type}-${Date.now()}-${Math.random().toString(16).slice(2, 8)}`,
    type,
    config:
      type === "send_email"
        ? { subject: "", content: "" }
        : type === "wait"
          ? { duration: 1, unit: "hours" }
          : type === "condition"
            ? { attribute: "email_score", operator: "gte", value: 50 }
            : type === "update_score"
              ? { delta: 5 }
              : type === "add_tag"
                ? { tag: "" }
                : {},
    next_step_id: null,
    else_step_id: null,
    position_x: 0,
    position_y: 0,
  };
}

export function WorkflowBuilder({
  value,
  onChange,
  onSave,
}: WorkflowBuilderProps) {
  const [selectedStepId, setSelectedStepId] = useState<string | null>(
    value.entry_step_id || value.steps[0]?.id || null
  );

  const selectedStep =
    value.steps.find((step) => step.id === selectedStepId) || null;

  const stepOptions = useMemo(
    () =>
      value.steps.map((step) => ({
        value: step.id || "",
        label: `${step.type.replaceAll("_", " ")}${value.entry_step_id === step.id ? " (entry)" : ""}`,
      })),
    [value.entry_step_id, value.steps]
  );

  const updateStep = (updatedStep: WorkflowStep) => {
    onChange({
      ...value,
      steps: value.steps.map((step) =>
        step.id === updatedStep.id ? updatedStep : step
      ),
    });
  };

  const removeStep = (stepId?: string) => {
    if (!stepId) return;

    const nextSteps = value.steps
      .filter((step) => step.id !== stepId)
      .map((step) => ({
        ...step,
        next_step_id: step.next_step_id === stepId ? null : step.next_step_id,
        else_step_id: step.else_step_id === stepId ? null : step.else_step_id,
      }));

    onChange({
      ...value,
      entry_step_id:
        value.entry_step_id === stepId
          ? nextSteps[0]?.id || null
          : value.entry_step_id,
      steps: nextSteps,
    });

    if (selectedStepId === stepId) {
      setSelectedStepId(nextSteps[0]?.id || null);
    }
  };

  return (
    <div className="grid gap-6 xl:grid-cols-[1.2fr_0.8fr]">
      <div className="space-y-6">
        <Card>
          <CardHeader>
            <CardTitle>Workflow settings</CardTitle>
          </CardHeader>
          <CardContent className="grid gap-4 md:grid-cols-2">
            <div className="space-y-2 md:col-span-2">
              <Label htmlFor="workflow-name">Name</Label>
              <Input
                id="workflow-name"
                value={value.name}
                onChange={(event) =>
                  onChange({ ...value, name: event.target.value })
                }
              />
            </div>
            <div className="space-y-2 md:col-span-2">
              <Label htmlFor="workflow-description">Description</Label>
              <Textarea
                id="workflow-description"
                value={value.description || ""}
                rows={3}
                onChange={(event) =>
                  onChange({ ...value, description: event.target.value })
                }
              />
            </div>
            <div className="space-y-2">
              <Label htmlFor="workflow-trigger">Trigger</Label>
              <select
                id="workflow-trigger"
                className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                value={value.trigger_type}
                onChange={(event) =>
                  onChange({
                    ...value,
                    trigger_type: event.target
                      .value as Workflow["trigger_type"],
                  })
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
            <div className="space-y-2">
              <Label htmlFor="workflow-status">Status</Label>
              <select
                id="workflow-status"
                className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                value={value.status}
                onChange={(event) =>
                  onChange({
                    ...value,
                    status: event.target.value as Workflow["status"],
                  })
                }
              >
                <option value="draft">Draft</option>
                <option value="active">Active</option>
                <option value="paused">Paused</option>
                <option value="archived">Archived</option>
              </select>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-row items-center justify-between gap-4">
            <CardTitle>Workflow builder</CardTitle>
            <div className="flex flex-wrap gap-2">
              {STEP_TYPES.map((type) => (
                <Button
                  key={type}
                  type="button"
                  variant="outline"
                  size="sm"
                  onClick={() => {
                    const step = createStep(type);
                    onChange({
                      ...value,
                      entry_step_id: value.entry_step_id || step.id || null,
                      steps: [...value.steps, step],
                    });
                    setSelectedStepId(step.id || null);
                  }}
                >
                  Add {type.replaceAll("_", " ")}
                </Button>
              ))}
            </div>
          </CardHeader>
          <CardContent className="grid gap-4 md:grid-cols-2">
            {value.steps.map((step) => (
              <div
                key={step.id}
                className={`rounded-xl border p-4 transition ${
                  step.id === selectedStepId
                    ? "border-primary bg-primary/5"
                    : "border-border"
                }`}
              >
                <button
                  type="button"
                  className="w-full text-left"
                  onClick={() => setSelectedStepId(step.id || null)}
                >
                  <p className="text-sm font-semibold capitalize">
                    {step.type.replaceAll("_", " ")}
                  </p>
                  <p className="mt-1 text-xs text-muted-foreground">
                    {step.id === value.entry_step_id ? "Entry node" : "Node"}
                  </p>
                </button>
                <div className="mt-4 grid gap-3">
                  <div className="space-y-1">
                    <Label htmlFor={`next-${step.id}`}>Next step</Label>
                    <select
                      id={`next-${step.id}`}
                      className="flex h-9 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                      value={step.next_step_id || ""}
                      onChange={(event) =>
                        updateStep({
                          ...step,
                          next_step_id: event.target.value || null,
                        })
                      }
                    >
                      <option value="">None</option>
                      {stepOptions
                        .filter((option) => option.value !== step.id)
                        .map((option) => (
                          <option key={option.value} value={option.value}>
                            {option.label}
                          </option>
                        ))}
                    </select>
                  </div>
                  {step.type === "condition" ? (
                    <div className="space-y-1">
                      <Label htmlFor={`else-${step.id}`}>Else step</Label>
                      <select
                        id={`else-${step.id}`}
                        className="flex h-9 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                        value={step.else_step_id || ""}
                        onChange={(event) =>
                          updateStep({
                            ...step,
                            else_step_id: event.target.value || null,
                          })
                        }
                      >
                        <option value="">None</option>
                        {stepOptions
                          .filter((option) => option.value !== step.id)
                          .map((option) => (
                            <option key={option.value} value={option.value}>
                              {option.label}
                            </option>
                          ))}
                      </select>
                    </div>
                  ) : null}
                </div>
                <div className="mt-4 flex gap-2">
                  <Button
                    type="button"
                    variant="secondary"
                    size="sm"
                    onClick={() =>
                      onChange({ ...value, entry_step_id: step.id || null })
                    }
                  >
                    Make entry
                  </Button>
                  <Button
                    type="button"
                    variant="ghost"
                    size="sm"
                    onClick={() => removeStep(step.id)}
                  >
                    Remove
                  </Button>
                </div>
              </div>
            ))}
          </CardContent>
        </Card>

        <Button type="button" onClick={() => void onSave()}>
          Save workflow
        </Button>
      </div>

      <WorkflowNodeConfig
        step={selectedStep}
        onChange={updateStep}
        onClose={() => setSelectedStepId(null)}
      />
    </div>
  );
}

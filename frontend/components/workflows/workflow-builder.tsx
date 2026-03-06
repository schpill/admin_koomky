"use client";

import { useMemo, useState } from "react";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { WorkflowNodeConfig } from "@/components/workflows/workflow-node-config";
import type { WorkflowStep, WorkflowStepType } from "@/lib/stores/workflows";

interface WorkflowBuilderProps {
  value: WorkflowStep[];
  entryStepId?: string | null;
  onChange: (steps: WorkflowStep[], entryStepId?: string | null) => void;
}

const stepTemplates: Array<{
  type: WorkflowStepType;
  label: string;
  config: Record<string, unknown>;
}> = [
  { type: "send_email", label: "Send email", config: { subject: "", content: "" } },
  { type: "wait", label: "Wait", config: { duration: 1, unit: "hours" } },
  {
    type: "condition",
    label: "Condition",
    config: { attribute: "email_score", operator: "gte", value: "50" },
  },
  { type: "update_score", label: "Update score", config: { delta: 10 } },
  { type: "add_tag", label: "Add tag", config: { tag: "" } },
  { type: "remove_tag", label: "Remove tag", config: { tag: "" } },
  { type: "update_field", label: "Update field", config: { field: "", value: "" } },
  { type: "enroll_drip", label: "Enroll drip", config: { sequence_id: "" } },
  { type: "end", label: "End", config: {} },
];

function temporaryStepId(type: WorkflowStepType): string {
  return `local-${type}-${Math.random().toString(36).slice(2, 10)}`;
}

export function WorkflowBuilder({
  value,
  entryStepId,
  onChange,
}: WorkflowBuilderProps) {
  const [selectedId, setSelectedId] = useState<string | null>(value[0]?.id || null);

  const selectedStep = useMemo(
    () => value.find((step) => step.id === selectedId) || null,
    [selectedId, value]
  );

  const updateStep = (stepId: string, updater: (current: WorkflowStep) => WorkflowStep) => {
    onChange(
      value.map((step) => (step.id === stepId ? updater(step) : step)),
      entryStepId
    );
  };

  const addStep = (type: WorkflowStepType, config: Record<string, unknown>) => {
    const nextStep: WorkflowStep = {
      id: temporaryStepId(type),
      type,
      config,
      next_step_id: null,
      else_step_id: null,
      position_x: value.length * 240,
      position_y: 0,
    };
    const previous = value[value.length - 1];
    const steps = [...value];
    if (previous) {
      steps[steps.length - 1] = {
        ...previous,
        next_step_id: previous.next_step_id || nextStep.id,
      };
    }
    steps.push(nextStep);
    onChange(steps, entryStepId || nextStep.id || null);
    setSelectedId(nextStep.id || null);
  };

  const removeStep = (stepId: string) => {
    const nextSteps = value
      .filter((step) => step.id !== stepId)
      .map((step) => ({
        ...step,
        next_step_id: step.next_step_id === stepId ? null : step.next_step_id,
        else_step_id: step.else_step_id === stepId ? null : step.else_step_id,
      }));
    const nextEntry = entryStepId === stepId ? nextSteps[0]?.id || null : entryStepId;
    onChange(nextSteps, nextEntry);
    setSelectedId(nextSteps[0]?.id || null);
  };

  return (
    <div className="grid gap-6 xl:grid-cols-[260px_minmax(0,1fr)_360px]">
      <Card>
        <CardHeader>
          <CardTitle>Node palette</CardTitle>
        </CardHeader>
        <CardContent className="grid gap-2">
          {stepTemplates.map((template) => (
            <Button
              key={template.type}
              type="button"
              variant="outline"
              className="justify-start"
              onClick={() => addStep(template.type, template.config)}
            >
              {template.label}
            </Button>
          ))}
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>Workflow graph</CardTitle>
        </CardHeader>
        <CardContent className="space-y-4">
          {value.length === 0 ? (
            <div className="rounded-lg border border-dashed p-6 text-sm text-muted-foreground">
              Add a node to start the workflow.
            </div>
          ) : (
            <div className="grid gap-4 md:grid-cols-2">
              {value.map((step, index) => (
                <div
                  key={step.id || `${step.type}-${index}`}
                  className={`rounded-xl border p-4 transition ${
                    selectedId === step.id ? "border-primary shadow-sm" : "border-border"
                  }`}
                >
                  <div className="flex items-start justify-between gap-3">
                    <div>
                      <p className="font-medium">{step.type}</p>
                      <p className="text-xs text-muted-foreground">
                        Node {index + 1}
                      </p>
                    </div>
                    {entryStepId === step.id ? <Badge>Entry</Badge> : null}
                  </div>

                  <div className="mt-4 grid gap-3">
                    <Button
                      type="button"
                      variant="outline"
                      size="sm"
                      onClick={() => setSelectedId(step.id || null)}
                    >
                      Configure
                    </Button>

                    <div className="space-y-2">
                      <label className="text-xs font-medium text-muted-foreground">
                        Next step
                      </label>
                      <select
                        className="flex h-9 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                        value={String(step.next_step_id || "")}
                        onChange={(event) =>
                          updateStep(step.id || "", (current) => ({
                            ...current,
                            next_step_id: event.target.value || null,
                          }))
                        }
                      >
                        <option value="">None</option>
                        {value
                          .filter((candidate) => candidate.id !== step.id)
                          .map((candidate, candidateIndex) => (
                            <option key={candidate.id} value={candidate.id}>
                              {candidateIndex + 1}. {candidate.type}
                            </option>
                          ))}
                      </select>
                    </div>

                    {step.type === "condition" ? (
                      <div className="space-y-2">
                        <label className="text-xs font-medium text-muted-foreground">
                          Else step
                        </label>
                        <select
                          className="flex h-9 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                          value={String(step.else_step_id || "")}
                          onChange={(event) =>
                            updateStep(step.id || "", (current) => ({
                              ...current,
                              else_step_id: event.target.value || null,
                            }))
                          }
                        >
                          <option value="">None</option>
                          {value
                            .filter((candidate) => candidate.id !== step.id)
                            .map((candidate, candidateIndex) => (
                              <option key={candidate.id} value={candidate.id}>
                                {candidateIndex + 1}. {candidate.type}
                              </option>
                            ))}
                        </select>
                      </div>
                    ) : null}

                    <div className="grid gap-2 md:grid-cols-2">
                      <div className="space-y-1">
                        <label className="text-xs font-medium text-muted-foreground">
                          X
                        </label>
                        <Input
                          type="number"
                          value={step.position_x}
                          onChange={(event) =>
                            updateStep(step.id || "", (current) => ({
                              ...current,
                              position_x: Number(event.target.value || 0),
                            }))
                          }
                        />
                      </div>
                      <div className="space-y-1">
                        <label className="text-xs font-medium text-muted-foreground">
                          Y
                        </label>
                        <Input
                          type="number"
                          value={step.position_y}
                          onChange={(event) =>
                            updateStep(step.id || "", (current) => ({
                              ...current,
                              position_y: Number(event.target.value || 0),
                            }))
                          }
                        />
                      </div>
                    </div>
                  </div>

                  <div className="mt-4 flex gap-2">
                    <Button
                      type="button"
                      variant="secondary"
                      size="sm"
                      onClick={() => onChange(value, step.id || null)}
                    >
                      Make entry
                    </Button>
                    <Button
                      type="button"
                      variant="destructive"
                      size="sm"
                      onClick={() => removeStep(step.id || "")}
                    >
                      Remove
                    </Button>
                  </div>
                </div>
              ))}
            </div>
          )}
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>Node config</CardTitle>
        </CardHeader>
        <CardContent>
          <WorkflowNodeConfig
            step={selectedStep}
            onChange={(next) => {
              if (!next.id) return;
              updateStep(next.id, () => next);
            }}
          />
        </CardContent>
      </Card>
    </div>
  );
}

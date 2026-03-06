"use client";

import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import type { WorkflowStep } from "@/lib/stores/workflows";

interface WorkflowNodeConfigProps {
  step: WorkflowStep | null;
  onChange: (next: WorkflowStep) => void;
}

export function WorkflowNodeConfig({
  step,
  onChange,
}: WorkflowNodeConfigProps) {
  if (!step) {
    return (
      <div className="rounded-lg border border-dashed p-4 text-sm text-muted-foreground">
        Select a node to configure it.
      </div>
    );
  }

  const config = step.config || {};

  const updateConfig = (patch: Record<string, unknown>) => {
    onChange({
      ...step,
      config: {
        ...config,
        ...patch,
      },
    });
  };

  return (
    <div className="space-y-4 rounded-lg border p-4">
      <div>
        <p className="text-sm font-medium">Node type</p>
        <p className="text-sm text-muted-foreground">{step.type}</p>
      </div>

      {step.type === "send_email" ? (
        <>
          <div className="space-y-2">
            <Label htmlFor="workflow-step-subject">Subject</Label>
            <Input
              id="workflow-step-subject"
              value={String(config.subject || "")}
              onChange={(event) =>
                updateConfig({ subject: event.target.value })
              }
            />
          </div>
          <div className="space-y-2">
            <Label htmlFor="workflow-step-content">Content</Label>
            <Textarea
              id="workflow-step-content"
              value={String(config.content || "")}
              rows={6}
              onChange={(event) =>
                updateConfig({ content: event.target.value })
              }
            />
          </div>
        </>
      ) : null}

      {step.type === "wait" ? (
        <div className="grid gap-4 md:grid-cols-2">
          <div className="space-y-2">
            <Label htmlFor="workflow-step-duration">Duration</Label>
            <Input
              id="workflow-step-duration"
              type="number"
              min={0}
              value={Number(config.duration || 0)}
              onChange={(event) =>
                updateConfig({ duration: Number(event.target.value || 0) })
              }
            />
          </div>
          <div className="space-y-2">
            <Label htmlFor="workflow-step-unit">Unit</Label>
            <select
              id="workflow-step-unit"
              className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
              value={String(config.unit || "hours")}
              onChange={(event) => updateConfig({ unit: event.target.value })}
            >
              <option value="hours">Hours</option>
              <option value="days">Days</option>
            </select>
          </div>
        </div>
      ) : null}

      {step.type === "condition" ? (
        <div className="grid gap-4">
          <div className="space-y-2">
            <Label htmlFor="workflow-step-attribute">Attribute</Label>
            <Input
              id="workflow-step-attribute"
              value={String(config.attribute || "")}
              onChange={(event) =>
                updateConfig({ attribute: event.target.value })
              }
            />
          </div>
          <div className="space-y-2">
            <Label htmlFor="workflow-step-operator">Operator</Label>
            <select
              id="workflow-step-operator"
              className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
              value={String(config.operator || "eq")}
              onChange={(event) =>
                updateConfig({ operator: event.target.value })
              }
            >
              <option value="eq">Equals</option>
              <option value="neq">Not equal</option>
              <option value="gt">Greater than</option>
              <option value="gte">Greater or equal</option>
              <option value="lt">Lower than</option>
              <option value="lte">Lower or equal</option>
              <option value="contains">Contains</option>
            </select>
          </div>
          <div className="space-y-2">
            <Label htmlFor="workflow-step-value">Value</Label>
            <Input
              id="workflow-step-value"
              value={String(config.value || "")}
              onChange={(event) => updateConfig({ value: event.target.value })}
            />
          </div>
        </div>
      ) : null}

      {step.type === "update_score" ? (
        <div className="space-y-2">
          <Label htmlFor="workflow-step-delta">Score delta</Label>
          <Input
            id="workflow-step-delta"
            type="number"
            value={Number(config.delta || 0)}
            onChange={(event) =>
              updateConfig({ delta: Number(event.target.value || 0) })
            }
          />
        </div>
      ) : null}

      {["add_tag", "remove_tag"].includes(step.type) ? (
        <div className="space-y-2">
          <Label htmlFor="workflow-step-tag">Tag</Label>
          <Input
            id="workflow-step-tag"
            value={String(config.tag || "")}
            onChange={(event) => updateConfig({ tag: event.target.value })}
          />
        </div>
      ) : null}

      {step.type === "update_field" ? (
        <div className="grid gap-4 md:grid-cols-2">
          <div className="space-y-2">
            <Label htmlFor="workflow-step-field">Field</Label>
            <Input
              id="workflow-step-field"
              value={String(config.field || "")}
              onChange={(event) => updateConfig({ field: event.target.value })}
            />
          </div>
          <div className="space-y-2">
            <Label htmlFor="workflow-step-field-value">Value</Label>
            <Input
              id="workflow-step-field-value"
              value={String(config.value || "")}
              onChange={(event) => updateConfig({ value: event.target.value })}
            />
          </div>
        </div>
      ) : null}

      {step.type === "enroll_drip" ? (
        <div className="space-y-2">
          <Label htmlFor="workflow-step-sequence">Drip sequence ID</Label>
          <Input
            id="workflow-step-sequence"
            value={String(config.sequence_id || "")}
            onChange={(event) =>
              updateConfig({ sequence_id: event.target.value })
            }
          />
        </div>
      ) : null}
    </div>
  );
}

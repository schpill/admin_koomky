"use client";

import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import type { WorkflowStep } from "@/lib/stores/workflows";

interface WorkflowNodeConfigProps {
  step: WorkflowStep | null;
  onChange: (next: WorkflowStep) => void;
  onClose?: () => void;
}

export type { WorkflowStep };

export function WorkflowNodeConfig({
  step,
  onChange,
  onClose,
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
      <div className="flex items-start justify-between gap-3">
        <div>
          <p className="text-sm font-medium">Node type</p>
          <p className="text-sm text-muted-foreground">{step.type}</p>
        </div>
        {onClose ? (
          <button
            type="button"
            className="text-xs text-muted-foreground hover:text-foreground"
            onClick={onClose}
          >
            Close
          </button>
        ) : null}
      </div>

      {step.type === "send_email" ? (
        <>
          <div className="space-y-2">
            <Label htmlFor="workflow-step-subject">Subject</Label>
            <Input
              id="workflow-step-subject"
              aria-label="Subject"
              value={String(config.subject || "")}
              onChange={(event) => updateConfig({ subject: event.target.value })}
            />
          </div>
          <div className="space-y-2">
            <Label htmlFor="workflow-step-content">Content</Label>
            <Textarea
              id="workflow-step-content"
              aria-label="Content"
              value={String(config.content || "")}
              rows={6}
              onChange={(event) => updateConfig({ content: event.target.value })}
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
              aria-label="Duration"
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
              aria-label="Unit"
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
            <Label htmlFor="workflow-attribute">Attribute</Label>
            <Input
              id="workflow-attribute"
              aria-label="Attribute"
              value={String(config.attribute || "")}
              onChange={(event) =>
                updateConfig({ attribute: event.target.value })
              }
            />
          </div>
          <div className="space-y-2">
            <Label htmlFor="workflow-operator">Operator</Label>
            <select
              id="workflow-operator"
              aria-label="Operator"
              className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
              value={String(config.operator || "equals")}
              onChange={(event) => updateConfig({ operator: event.target.value })}
            >
              <option value="equals">Equals</option>
              <option value="gte">Greater than or equal</option>
              <option value="lte">Less than or equal</option>
              <option value="contains">Contains</option>
            </select>
          </div>
          <div className="space-y-2">
            <Label htmlFor="workflow-value">Value</Label>
            <Input
              id="workflow-value"
              aria-label="Value"
              value={String(config.value || "")}
              onChange={(event) => updateConfig({ value: event.target.value })}
            />
          </div>
        </div>
      ) : null}

      {step.type === "update_score" ? (
        <div className="space-y-2">
          <Label htmlFor="workflow-delta">Score delta</Label>
          <Input
            id="workflow-delta"
            aria-label="Score delta"
            type="number"
            value={String(config.delta || 0)}
            onChange={(event) =>
              updateConfig({ delta: Number(event.target.value || 0) })
            }
          />
        </div>
      ) : null}

      {step.type === "add_tag" || step.type === "remove_tag" ? (
        <div className="space-y-2">
          <Label htmlFor="workflow-tag">Tag</Label>
          <Input
            id="workflow-tag"
            aria-label="Tag"
            value={String(config.tag || "")}
            onChange={(event) => updateConfig({ tag: event.target.value })}
          />
        </div>
      ) : null}

      {step.type === "end" ? (
        <p className="text-sm text-muted-foreground">
          This node completes the workflow.
        </p>
      ) : null}
    </div>
  );
}

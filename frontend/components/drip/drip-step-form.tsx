"use client";

import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import type { DripStepInput } from "@/lib/stores/drip-sequences";

interface DripStepFormProps {
  value: DripStepInput;
  onChange: (next: DripStepInput) => void;
}

export function DripStepForm({ value, onChange }: DripStepFormProps) {
  return (
    <div className="grid gap-4 rounded-lg border p-4">
      <div className="grid gap-2 md:grid-cols-3">
        <div className="space-y-2">
          <Label htmlFor={`subject-${value.position}`}>Subject</Label>
          <Input
            id={`subject-${value.position}`}
            aria-label="Subject"
            value={value.subject}
            onChange={(event) =>
              onChange({ ...value, subject: event.target.value })
            }
          />
        </div>
        <div className="space-y-2">
          <Label htmlFor={`delay-${value.position}`}>Delay (hours)</Label>
          <Input
            id={`delay-${value.position}`}
            type="number"
            value={value.delay_hours}
            onChange={(event) =>
              onChange({
                ...value,
                delay_hours: Number(event.target.value || 0),
              })
            }
          />
        </div>
        <div className="space-y-2">
          <Label htmlFor={`condition-${value.position}`}>Condition</Label>
          <select
            id={`condition-${value.position}`}
            className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
            value={value.condition}
            onChange={(event) =>
              onChange({
                ...value,
                condition: event.target.value as DripStepInput["condition"],
              })
            }
          >
            <option value="none">No condition</option>
            <option value="if_opened">If previous email opened</option>
            <option value="if_clicked">If previous email clicked</option>
            <option value="if_not_opened">If previous email not opened</option>
          </select>
        </div>
      </div>

      <div className="space-y-2">
        <Label htmlFor={`content-${value.position}`}>Content</Label>
        <Textarea
          id={`content-${value.position}`}
          value={value.content}
          rows={6}
          onChange={(event) =>
            onChange({ ...value, content: event.target.value })
          }
        />
      </div>
    </div>
  );
}

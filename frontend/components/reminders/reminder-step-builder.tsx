"use client";

import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Textarea } from "@/components/ui/textarea";
import type { ReminderStepInput } from "@/lib/stores/reminders";

interface ReminderStepBuilderProps {
  value: ReminderStepInput[];
  onChange: (steps: ReminderStepInput[]) => void;
}

export function ReminderStepBuilder({
  value,
  onChange,
}: ReminderStepBuilderProps) {
  const setStep = (
    index: number,
    field: keyof ReminderStepInput,
    nextValue: string | number
  ) => {
    const next = value.map((step, stepIndex) =>
      stepIndex === index ? { ...step, [field]: nextValue } : step
    );
    onChange(next.map((step, idx) => ({ ...step, step_number: idx + 1 })));
  };

  const addStep = () => {
    if (value.length >= 10) {
      return;
    }

    onChange([
      ...value,
      {
        step_number: value.length + 1,
        delay_days: 3,
        subject: "",
        body: "",
      },
    ]);
  };

  const removeStep = (index: number) => {
    onChange(
      value
        .filter((_, stepIndex) => stepIndex !== index)
        .map((step, idx) => ({ ...step, step_number: idx + 1 }))
    );
  };

  return (
    <div className="space-y-4">
      {value.map((step, index) => (
        <div key={`step-${index}`} className="rounded-md border p-4 space-y-3">
          <div className="flex items-center justify-between">
            <p className="text-sm font-medium">Étape {index + 1}</p>
            <Button
              type="button"
              variant="ghost"
              onClick={() => removeStep(index)}
              disabled={value.length <= 1}
            >
              Supprimer
            </Button>
          </div>
          <div className="grid gap-3 md:grid-cols-2">
            <div>
              <label className="text-xs text-muted-foreground">
                Délai (jours)
              </label>
              <Input
                type="number"
                min={1}
                max={365}
                value={step.delay_days}
                onChange={(event) =>
                  setStep(index, "delay_days", Number(event.target.value) || 1)
                }
              />
            </div>
            <div>
              <label className="text-xs text-muted-foreground">Objet</label>
              <Input
                value={step.subject}
                onChange={(event) =>
                  setStep(index, "subject", event.target.value)
                }
              />
            </div>
          </div>
          <div>
            <label className="text-xs text-muted-foreground">Message</label>
            <Textarea
              value={step.body}
              onChange={(event) => setStep(index, "body", event.target.value)}
              rows={4}
            />
          </div>
        </div>
      ))}

      <Button
        type="button"
        variant="outline"
        onClick={addStep}
        disabled={value.length >= 10}
      >
        + Ajouter étape
      </Button>
    </div>
  );
}

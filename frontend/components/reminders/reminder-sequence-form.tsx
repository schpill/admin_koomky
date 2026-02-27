"use client";

import { useState } from "react";
import { z } from "zod";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Textarea } from "@/components/ui/textarea";
import { ReminderStepBuilder } from "@/components/reminders/reminder-step-builder";
import type { ReminderStepInput } from "@/lib/stores/reminders";

export const reminderSequenceSchema = z.object({
  name: z.string().min(1).max(255),
  description: z.string().max(1000).optional().nullable(),
  is_active: z.boolean().default(true),
  is_default: z.boolean().default(false),
  steps: z
    .array(
      z.object({
        step_number: z.number().int().min(1),
        delay_days: z.number().int().min(1).max(365),
        subject: z.string().min(1).max(255),
        body: z.string().min(1).max(10000),
      })
    )
    .min(1),
});

export type ReminderSequenceFormData = z.infer<typeof reminderSequenceSchema>;

interface ReminderSequenceFormProps {
  defaultValues?: Partial<ReminderSequenceFormData>;
  isLoading?: boolean;
  onSubmit: (data: ReminderSequenceFormData) => Promise<void> | void;
}

const defaultSteps: ReminderStepInput[] = [
  {
    step_number: 1,
    delay_days: 3,
    subject: "Rappel amical - Facture {{invoice_number}}",
    body: "Bonjour {{client_name}},\n\nLa facture {{invoice_number}} est en attente.",
  },
];

export function ReminderSequenceForm({
  defaultValues,
  isLoading,
  onSubmit,
}: ReminderSequenceFormProps) {
  const [name, setName] = useState(defaultValues?.name || "");
  const [description, setDescription] = useState(defaultValues?.description || "");
  const [isActive, setIsActive] = useState(defaultValues?.is_active ?? true);
  const [isDefault, setIsDefault] = useState(defaultValues?.is_default ?? false);
  const [steps, setSteps] = useState<ReminderStepInput[]>(
    defaultValues?.steps || defaultSteps
  );
  const [error, setError] = useState<string | null>(null);

  const submit = async (event: React.FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    setError(null);

    const parsed = reminderSequenceSchema.safeParse({
      name,
      description,
      is_active: isActive,
      is_default: isDefault,
      steps,
    });

    if (!parsed.success) {
      setError(parsed.error.issues[0]?.message || "Formulaire invalide");
      return;
    }

    await onSubmit(parsed.data);
  };

  return (
    <form onSubmit={submit} className="space-y-5">
      <div className="space-y-2">
        <label htmlFor="reminder-seq-name" className="text-sm font-medium">
          Nom
        </label>
        <Input
          id="reminder-seq-name"
          value={name}
          onChange={(event) => setName(event.target.value)}
        />
      </div>

      <div className="space-y-2">
        <label htmlFor="reminder-seq-description" className="text-sm font-medium">
          Description
        </label>
        <Textarea
          id="reminder-seq-description"
          value={description || ""}
          onChange={(event) => setDescription(event.target.value)}
        />
      </div>

      <div className="flex flex-wrap gap-4 text-sm">
        <label className="flex items-center gap-2">
          <input
            type="checkbox"
            checked={isActive}
            onChange={(event) => setIsActive(event.target.checked)}
          />
          Active
        </label>
        <label className="flex items-center gap-2">
          <input
            type="checkbox"
            checked={isDefault}
            onChange={(event) => setIsDefault(event.target.checked)}
          />
          Séquence par défaut
        </label>
      </div>

      <ReminderStepBuilder value={steps} onChange={setSteps} />

      {error ? <p className="text-sm text-red-600">{error}</p> : null}

      <Button type="submit" disabled={isLoading}>
        {isLoading ? "Enregistrement..." : "Enregistrer"}
      </Button>
    </form>
  );
}

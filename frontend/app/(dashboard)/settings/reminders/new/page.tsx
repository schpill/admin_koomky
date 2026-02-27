"use client";

import { useRouter } from "next/navigation";
import { toast } from "sonner";
import { ReminderSequenceForm } from "@/components/reminders/reminder-sequence-form";
import { useReminderStore } from "@/lib/stores/reminders";

export default function NewReminderSequencePage() {
  const router = useRouter();
  const { createSequence, isLoading } = useReminderStore();

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-3xl font-bold">Nouvelle séquence</h1>
        <p className="text-sm text-muted-foreground">
          Configurez les étapes de relance de vos factures impayées.
        </p>
      </div>

      <ReminderSequenceForm
        isLoading={isLoading}
        onSubmit={async (data) => {
          try {
            const created = await createSequence(data);
            toast.success("Séquence créée");
            router.push(`/settings/reminders/${created.id}`);
          } catch (error) {
            toast.error((error as Error).message);
          }
        }}
      />
    </div>
  );
}

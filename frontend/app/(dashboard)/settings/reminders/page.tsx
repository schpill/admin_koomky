"use client";

import { useEffect } from "react";
import Link from "next/link";
import { useRouter } from "next/navigation";
import { toast } from "sonner";
import { Button } from "@/components/ui/button";
import { ReminderSequenceCard } from "@/components/reminders/reminder-sequence-card";
import { useReminderStore } from "@/lib/stores/reminders";

export default function ReminderSettingsPage() {
  const router = useRouter();
  const {
    sequences,
    fetchSequences,
    updateSequence,
    deleteSequence,
    setDefaultSequence,
  } = useReminderStore();

  useEffect(() => {
    fetchSequences();
  }, [fetchSequences]);

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold">Relances automatiques</h1>
          <p className="text-sm text-muted-foreground">
            Créez et gérez vos séquences de relance email.
          </p>
        </div>
        <Button asChild>
          <Link href="/settings/reminders/new">+ Nouvelle séquence</Link>
        </Button>
      </div>

      {sequences.length === 0 ? (
        <div className="rounded border border-dashed p-8 text-sm text-muted-foreground">
          Aucune séquence pour le moment. Commencez par en créer une.
        </div>
      ) : (
        <div className="grid gap-4 md:grid-cols-2">
          {sequences.map((sequence) => (
            <ReminderSequenceCard
              key={sequence.id}
              sequence={sequence}
              onEdit={() => router.push(`/settings/reminders/${sequence.id}`)}
              onSetDefault={async () => {
                await setDefaultSequence(sequence.id);
                toast.success("Séquence par défaut mise à jour");
              }}
              onDelete={async () => {
                await deleteSequence(sequence.id);
                toast.success("Séquence supprimée");
              }}
              onToggleActive={async (active) => {
                await updateSequence(sequence.id, { is_active: active });
              }}
            />
          ))}
        </div>
      )}
    </div>
  );
}

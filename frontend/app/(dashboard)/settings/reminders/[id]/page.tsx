"use client";

import { useEffect, useMemo, useState } from "react";
import Link from "next/link";
import { useParams } from "next/navigation";
import { toast } from "sonner";
import { Button } from "@/components/ui/button";
import { EmailPreviewModal } from "@/components/reminders/email-preview-modal";
import { ReminderSequenceForm } from "@/components/reminders/reminder-sequence-form";
import { useReminderStore } from "@/lib/stores/reminders";

export default function ReminderSequenceDetailPage() {
  const params = useParams<{ id: string }>();
  const id = params.id;
  const [previewOpen, setPreviewOpen] = useState(false);
  const { sequences, fetchSequences, updateSequence, isLoading } =
    useReminderStore();

  useEffect(() => {
    fetchSequences();
  }, [fetchSequences]);

  const sequence = useMemo(
    () => sequences.find((item) => item.id === id) || null,
    [sequences, id]
  );

  const firstStep = sequence?.steps?.[0] || null;

  if (!sequence) {
    return (
      <div className="space-y-3">
        <p>Séquence introuvable.</p>
        <Button asChild>
          <Link href="/settings/reminders">Retour</Link>
        </Button>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold">{sequence.name}</h1>
          <p className="text-sm text-muted-foreground">
            {sequence.is_default
              ? "Séquence par défaut"
              : "Séquence personnalisée"}
          </p>
        </div>
        <Button
          type="button"
          variant="outline"
          onClick={() => setPreviewOpen(true)}
        >
          Aperçu email
        </Button>
      </div>

      <ReminderSequenceForm
        defaultValues={sequence}
        isLoading={isLoading}
        onSubmit={async (data) => {
          try {
            await updateSequence(sequence.id, data);
            toast.success("Séquence mise à jour");
          } catch (error) {
            toast.error((error as Error).message);
          }
        }}
      />

      <EmailPreviewModal
        open={previewOpen}
        onOpenChange={setPreviewOpen}
        step={firstStep}
      />
    </div>
  );
}

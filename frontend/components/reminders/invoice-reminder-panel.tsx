"use client";

import { useEffect, useMemo, useState } from "react";
import { toast } from "sonner";
import { Button } from "@/components/ui/button";
import { ReminderTimeline } from "@/components/reminders/reminder-timeline";
import { useReminderStore } from "@/lib/stores/reminders";

interface InvoiceReminderPanelProps {
  invoiceId: string;
}

export function InvoiceReminderPanel({ invoiceId }: InvoiceReminderPanelProps) {
  const {
    sequences,
    invoiceReminder,
    fetchSequences,
    fetchInvoiceReminder,
    attachSequence,
    pauseReminder,
    resumeReminder,
    skipStep,
    cancelReminder,
  } = useReminderStore();

  const [selectedSequence, setSelectedSequence] = useState("");

  useEffect(() => {
    fetchSequences();
    fetchInvoiceReminder(invoiceId);
  }, [fetchSequences, fetchInvoiceReminder, invoiceId]);

  const sortedSteps = useMemo(
    () =>
      (invoiceReminder?.sequence?.steps || [])
        .slice()
        .sort((a, b) => a.step_number - b.step_number),
    [invoiceReminder?.sequence?.steps]
  );

  const onAttach = async () => {
    if (!selectedSequence) {
      return;
    }

    try {
      await attachSequence(invoiceId, selectedSequence);
      toast.success("Séquence attachée");
    } catch (error) {
      toast.error((error as Error).message);
    }
  };

  if (!invoiceReminder) {
    return (
      <div className="space-y-3">
        <p className="text-sm text-muted-foreground">
          Aucune séquence attachée.
        </p>
        <div className="flex items-center gap-2">
          <select
            className="h-10 rounded-md border px-3 text-sm"
            value={selectedSequence}
            onChange={(event) => setSelectedSequence(event.target.value)}
          >
            <option value="">Choisir une séquence</option>
            {sequences.map((sequence) => (
              <option key={sequence.id} value={sequence.id}>
                {sequence.name}
              </option>
            ))}
          </select>
          <Button type="button" onClick={onAttach}>
            Attacher une séquence
          </Button>
        </div>
      </div>
    );
  }

  return (
    <div className="space-y-4">
      <div className="flex flex-wrap items-center gap-2">
        <p className="text-sm">
          Séquence:{" "}
          <span className="font-medium">
            {invoiceReminder.sequence?.name || "-"}
          </span>
        </p>
        <Button
          type="button"
          variant="outline"
          size="sm"
          onClick={() => pauseReminder(invoiceId)}
        >
          Pause
        </Button>
        <Button
          type="button"
          variant="outline"
          size="sm"
          onClick={() => resumeReminder(invoiceId)}
        >
          Reprendre
        </Button>
        <Button
          type="button"
          variant="outline"
          size="sm"
          onClick={() => skipStep(invoiceId)}
        >
          Sauter étape
        </Button>
        <Button
          type="button"
          variant="destructive"
          size="sm"
          onClick={() => cancelReminder(invoiceId)}
        >
          Détacher
        </Button>
      </div>

      <ReminderTimeline
        steps={sortedSteps}
        deliveries={invoiceReminder.deliveries || []}
      />
    </div>
  );
}

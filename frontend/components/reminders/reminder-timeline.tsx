"use client";

import { Check, Clock3, SkipForward, XCircle } from "lucide-react";
import type { ReminderDelivery, ReminderStepInput } from "@/lib/stores/reminders";

interface ReminderTimelineProps {
  steps: ReminderStepInput[];
  deliveries?: ReminderDelivery[];
}

function statusIcon(status: string) {
  if (status === "sent") {
    return <Check className="h-4 w-4 text-green-600" />;
  }
  if (status === "failed") {
    return <XCircle className="h-4 w-4 text-red-600" />;
  }
  if (status === "skipped") {
    return <SkipForward className="h-4 w-4 text-orange-600" />;
  }

  return <Clock3 className="h-4 w-4 text-gray-500" />;
}

export function ReminderTimeline({ steps, deliveries = [] }: ReminderTimelineProps) {
  return (
    <div className="space-y-3">
      {steps.map((step, index) => {
        const delivery = deliveries.find((item) => item.step?.step_number === step.step_number);
        const status = delivery?.status || "pending";

        return (
          <div key={`${step.step_number}-${index}`} className="flex items-start gap-3 rounded-md border p-3">
            <div className="mt-1">{statusIcon(status)}</div>
            <div className="space-y-1">
              <p className="text-sm font-medium">
                J+{step.delay_days} - {step.subject}
              </p>
              <p className="text-xs text-muted-foreground">Statut: {status}</p>
              {delivery?.sent_at ? (
                <p className="text-xs text-muted-foreground">Envoyé le {delivery.sent_at}</p>
              ) : null}
            </div>
          </div>
        );
      })}
    </div>
  );
}

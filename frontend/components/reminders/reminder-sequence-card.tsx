"use client";

import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import type { ReminderSequence } from "@/lib/stores/reminders";

interface ReminderSequenceCardProps {
  sequence: ReminderSequence;
  onEdit: () => void;
  onSetDefault: () => void;
  onDelete: () => void;
  onToggleActive: (active: boolean) => void;
}

export function ReminderSequenceCard({
  sequence,
  onEdit,
  onSetDefault,
  onDelete,
  onToggleActive,
}: ReminderSequenceCardProps) {
  return (
    <div
      className={`rounded-lg border p-4 space-y-3 ${
        sequence.is_active ? "bg-background" : "bg-muted/30"
      }`}
    >
      <div className="flex items-center justify-between gap-2">
        <h3 className="font-semibold">{sequence.name}</h3>
        {sequence.is_default ? <Badge>Par défaut</Badge> : null}
      </div>

      <p className="text-sm text-muted-foreground line-clamp-2">
        {sequence.description || "Aucune description"}
      </p>

      <div className="text-xs text-muted-foreground">
        {sequence.steps?.length || 0} étape(s)
      </div>

      <label className="flex items-center gap-2 text-sm">
        <input
          type="checkbox"
          checked={sequence.is_active}
          onChange={(event) => onToggleActive(event.target.checked)}
        />
        Active
      </label>

      <div className="flex flex-wrap gap-2">
        <Button type="button" variant="outline" size="sm" onClick={onEdit}>
          Modifier
        </Button>
        <Button
          type="button"
          variant="outline"
          size="sm"
          onClick={onSetDefault}
          disabled={sequence.is_default}
        >
          Définir par défaut
        </Button>
        <Button type="button" variant="destructive" size="sm" onClick={onDelete}>
          Supprimer
        </Button>
      </div>
    </div>
  );
}

"use client";

import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";

interface StoConfigProps {
  enabled: boolean;
  windowHours: number;
  knownContactsCount?: number;
  onEnabledChange: (enabled: boolean) => void;
  onWindowHoursChange: (hours: number) => void;
}

export function StoConfig({
  enabled,
  windowHours,
  knownContactsCount = 0,
  onEnabledChange,
  onWindowHoursChange,
}: StoConfigProps) {
  return (
    <div className="space-y-4 rounded-lg border p-4">
      <label className="flex items-center gap-2 text-sm font-medium">
        <input
          type="checkbox"
          checked={enabled}
          onChange={(event) => onEnabledChange(event.target.checked)}
        />
        Enable send time optimization
      </label>

      <div className="space-y-2">
        <Label htmlFor="sto-window-hours">Optimization window (hours)</Label>
        <Input
          id="sto-window-hours"
          type="number"
          min={1}
          max={48}
          value={windowHours}
          disabled={!enabled}
          onChange={(event) =>
            onWindowHoursChange(Number(event.target.value || 24))
          }
        />
      </div>

      <p className="text-sm text-muted-foreground">
        {knownContactsCount} contacts currently have a known optimal hour.
      </p>
    </div>
  );
}

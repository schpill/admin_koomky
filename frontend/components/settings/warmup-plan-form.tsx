"use client";

import { useState } from "react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import type { WarmupPlanPayload } from "@/lib/stores/warmup-plans";

interface WarmupPlanFormProps {
  initialValues?: WarmupPlanPayload;
  onSubmit: (values: WarmupPlanPayload) => Promise<void>;
}

export function WarmupPlanForm({
  initialValues,
  onSubmit,
}: WarmupPlanFormProps) {
  const [name, setName] = useState(initialValues?.name ?? "");
  const [dailyVolumeStart, setDailyVolumeStart] = useState(
    initialValues?.daily_volume_start ?? 25
  );
  const [dailyVolumeMax, setDailyVolumeMax] = useState(
    initialValues?.daily_volume_max ?? 500
  );
  const [incrementPercent, setIncrementPercent] = useState(
    initialValues?.increment_percent ?? 30
  );
  const [isSubmitting, setIsSubmitting] = useState(false);

  const handleSubmit = async (event: React.FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    setIsSubmitting(true);
    try {
      await onSubmit({
        name,
        daily_volume_start: Number(dailyVolumeStart),
        daily_volume_max: Number(dailyVolumeMax),
        increment_percent: Number(incrementPercent),
      });
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <form onSubmit={handleSubmit} className="space-y-4">
      <div className="space-y-2">
        <Label htmlFor="warmup-name">Name</Label>
        <Input
          id="warmup-name"
          value={name}
          onChange={(event) => setName(event.target.value)}
        />
      </div>
      <div className="grid gap-4 md:grid-cols-3">
        <div className="space-y-2">
          <Label htmlFor="warmup-start">Start volume</Label>
          <Input
            id="warmup-start"
            type="number"
            value={dailyVolumeStart}
            onChange={(event) => setDailyVolumeStart(Number(event.target.value))}
          />
        </div>
        <div className="space-y-2">
          <Label htmlFor="warmup-max">Max volume</Label>
          <Input
            id="warmup-max"
            type="number"
            value={dailyVolumeMax}
            onChange={(event) => setDailyVolumeMax(Number(event.target.value))}
          />
        </div>
        <div className="space-y-2">
          <Label htmlFor="warmup-increment">Increment %</Label>
          <Input
            id="warmup-increment"
            type="number"
            value={incrementPercent}
            onChange={(event) => setIncrementPercent(Number(event.target.value))}
          />
        </div>
      </div>
      <Button type="submit" disabled={isSubmitting}>
        Save
      </Button>
    </form>
  );
}

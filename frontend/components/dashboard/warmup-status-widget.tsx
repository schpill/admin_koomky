"use client";

import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";

interface WarmupStatusWidgetProps {
  currentDay: number;
  currentDailyLimit: number;
  sentToday: number;
}

export function WarmupStatusWidget({
  currentDay,
  currentDailyLimit,
  sentToday,
}: WarmupStatusWidgetProps) {
  const progress =
    currentDailyLimit > 0
      ? Math.min(100, (sentToday / currentDailyLimit) * 100)
      : 0;

  return (
    <Card>
      <CardHeader>
        <CardTitle>Warm-up in progress</CardTitle>
      </CardHeader>
      <CardContent className="space-y-3">
        <div className="grid gap-4 sm:grid-cols-3">
          <div>
            <p className="text-xs uppercase tracking-wide text-muted-foreground">
              Day
            </p>
            <p className="text-2xl font-semibold">{currentDay}</p>
          </div>
          <div>
            <p className="text-xs uppercase tracking-wide text-muted-foreground">
              Daily limit
            </p>
            <p className="text-2xl font-semibold">{currentDailyLimit}</p>
          </div>
          <div>
            <p className="text-xs uppercase tracking-wide text-muted-foreground">
              Sent today
            </p>
            <p className="text-2xl font-semibold">{sentToday}</p>
          </div>
        </div>
        <div className="h-2 overflow-hidden rounded-full bg-muted">
          <div
            className="h-full rounded-full bg-primary transition-all"
            style={{ width: `${progress}%` }}
          />
        </div>
      </CardContent>
    </Card>
  );
}

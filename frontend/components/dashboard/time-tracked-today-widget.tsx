"use client";

import { Clock3 } from "lucide-react";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";

interface TimeTrackedTodayWidgetProps {
  minutesToday: number;
  entriesCount: number;
}

function formatMinutes(minutes: number): string {
  const hours = Math.floor(minutes / 60);
  const remainingMinutes = minutes % 60;

  if (hours === 0) {
    return `${remainingMinutes} min`;
  }

  return `${hours} h ${remainingMinutes.toString().padStart(2, "0")}`;
}

export function TimeTrackedTodayWidget({
  minutesToday,
  entriesCount,
}: TimeTrackedTodayWidgetProps) {
  if (minutesToday <= 0) {
    return null;
  }

  return (
    <Card>
      <CardHeader className="pb-2">
        <CardTitle className="flex items-center gap-2 text-base">
          <Clock3 className="h-4 w-4" />
          Temps suivi aujourd&apos;hui
        </CardTitle>
      </CardHeader>
      <CardContent className="flex items-end justify-between gap-4">
        <div>
          <p className="text-2xl font-bold">{formatMinutes(minutesToday)}</p>
          <p className="text-sm text-muted-foreground">
            {entriesCount} entr{entriesCount > 1 ? "\u00e9es" : "\u00e9e"}
          </p>
        </div>
      </CardContent>
    </Card>
  );
}

"use client";

import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";

interface DripSummaryWidgetProps {
  sequencesCount: number;
  activeEnrollments: number;
}

export function DripSummaryWidget({
  sequencesCount,
  activeEnrollments,
}: DripSummaryWidgetProps) {
  return (
    <Card>
      <CardHeader className="pb-2">
        <CardTitle className="text-base">Drip sequences</CardTitle>
      </CardHeader>
      <CardContent className="space-y-1">
        <p className="text-2xl font-bold">{sequencesCount}</p>
        <p className="text-sm text-muted-foreground">
          {activeEnrollments} active enrollments
        </p>
      </CardContent>
    </Card>
  );
}

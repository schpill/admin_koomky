"use client";

import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";

interface WorkflowSummaryWidgetProps {
  workflowsCount: number;
  activeEnrollments: number;
}

export function WorkflowSummaryWidget({
  workflowsCount,
  activeEnrollments,
}: WorkflowSummaryWidgetProps) {
  return (
    <Card>
      <CardHeader className="pb-2">
        <CardTitle className="text-base">Active workflows</CardTitle>
      </CardHeader>
      <CardContent className="space-y-1">
        <p className="text-2xl font-bold">{workflowsCount}</p>
        <p className="text-sm text-muted-foreground">
          {activeEnrollments} active enrollments
        </p>
      </CardContent>
    </Card>
  );
}

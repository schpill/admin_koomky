"use client";

import { useEffect } from "react";
import Link from "next/link";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { useWorkflowStore } from "@/lib/stores/workflows";

export default function WorkflowsPage() {
  const { workflows, fetchWorkflows, isLoading } = useWorkflowStore();

  useEffect(() => {
    fetchWorkflows().catch(() => undefined);
  }, [fetchWorkflows]);

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between gap-3">
        <div>
          <h1 className="text-3xl font-bold">Workflows</h1>
          <p className="text-sm text-muted-foreground">
            Multi-step automations with triggers, waits and conditions.
          </p>
        </div>
        <Button asChild>
          <Link href="/campaigns/workflows/create">Create workflow</Link>
        </Button>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Workflow catalog</CardTitle>
        </CardHeader>
        <CardContent className="space-y-3">
          {isLoading && workflows.length === 0 ? (
            <p className="text-sm text-muted-foreground">Loading...</p>
          ) : workflows.length === 0 ? (
            <p className="text-sm text-muted-foreground">
              No workflows yet.
            </p>
          ) : (
            workflows.map((workflow) => (
              <div
                key={workflow.id}
                className="flex items-center justify-between rounded-lg border p-4"
              >
                <div className="space-y-1">
                  <Link
                    href={`/campaigns/workflows/${workflow.id}`}
                    className="font-medium text-primary hover:underline"
                  >
                    {workflow.name}
                  </Link>
                  <p className="text-sm text-muted-foreground">
                    Trigger: {workflow.trigger_type} • Steps: {workflow.steps.length} •
                    Active enrollments: {workflow.analytics?.active_enrollments || 0}
                  </p>
                  <p className="text-xs text-muted-foreground">
                    Completion rate: {Number(workflow.analytics?.completion_rate || 0).toFixed(2)}%
                  </p>
                </div>
                <Badge variant="secondary">{workflow.status}</Badge>
              </div>
            ))
          )}
        </CardContent>
      </Card>
    </div>
  );
}

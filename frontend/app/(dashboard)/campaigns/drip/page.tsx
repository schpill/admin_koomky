"use client";

import { useEffect } from "react";
import Link from "next/link";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { useDripSequencesStore } from "@/lib/stores/drip-sequences";

export default function DripSequencesPage() {
  const { sequences, fetchSequences, isLoading } = useDripSequencesStore();

  useEffect(() => {
    fetchSequences().catch(() => undefined);
  }, [fetchSequences]);

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between gap-3">
        <div>
          <h1 className="text-3xl font-bold">Drip Sequences</h1>
          <p className="text-sm text-muted-foreground">
            Multi-step automated campaigns with behavioral conditions.
          </p>
        </div>
        <Button asChild>
          <Link href="/campaigns/drip/create">Create sequence</Link>
        </Button>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Sequences</CardTitle>
        </CardHeader>
        <CardContent className="space-y-3">
          {isLoading && sequences.length === 0 ? (
            <p className="text-sm text-muted-foreground">Loading...</p>
          ) : sequences.length === 0 ? (
            <p className="text-sm text-muted-foreground">
              No drip sequences yet.
            </p>
          ) : (
            sequences.map((sequence) => (
              <div
                key={sequence.id}
                className="flex items-center justify-between rounded-lg border p-4"
              >
                <div className="space-y-1">
                  <Link
                    href={`/campaigns/drip/${sequence.id}`}
                    className="font-medium text-primary hover:underline"
                  >
                    {sequence.name}
                  </Link>
                  <p className="text-sm text-muted-foreground">
                    Trigger: {sequence.trigger_event} • Steps:{" "}
                    {sequence.steps.length} • Active enrollments:{" "}
                    {sequence.enrollments.filter((item) => item.status === "active").length}
                  </p>
                </div>
                <Badge variant="secondary">{sequence.status}</Badge>
              </div>
            ))
          )}
        </CardContent>
      </Card>
    </div>
  );
}

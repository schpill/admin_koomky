"use client";

import { useEffect } from "react";
import { useParams } from "next/navigation";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { DripEnrollmentsTable } from "@/components/drip/drip-enrollments-table";
import { useDripSequencesStore } from "@/lib/stores/drip-sequences";

export default function DripSequenceDetailPage() {
  const params = useParams<{ id: string }>();
  const {
    currentSequence,
    enrollments,
    fetchSequence,
    pauseEnrollment,
    cancelEnrollment,
  } = useDripSequencesStore();

  useEffect(() => {
    if (params?.id) {
      fetchSequence(params.id).catch(() => undefined);
    }
  }, [fetchSequence, params?.id]);

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-3xl font-bold">
          {currentSequence?.name || "Drip sequence"}
        </h1>
        <p className="text-sm text-muted-foreground">
          Review steps and manage enrollments.
        </p>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Steps</CardTitle>
        </CardHeader>
        <CardContent className="space-y-3">
          {(currentSequence?.steps || []).map((step) => (
            <div key={step.position} className="rounded-lg border p-4">
              <p className="font-medium">
                Step {step.position}: {step.subject}
              </p>
              <p className="text-sm text-muted-foreground">
                Delay {step.delay_hours}h • Condition {step.condition}
              </p>
            </div>
          ))}
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>Enrollments</CardTitle>
        </CardHeader>
        <CardContent>
          <DripEnrollmentsTable
            enrollments={enrollments}
            onPause={(id) => void pauseEnrollment(id)}
            onCancel={(id) => void cancelEnrollment(id)}
          />
        </CardContent>
      </Card>
    </div>
  );
}

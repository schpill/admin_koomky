"use client";

import { useEffect } from "react";
import { toast } from "sonner";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { WarmupPlanForm } from "@/components/settings/warmup-plan-form";
import { useWarmupPlansStore } from "@/lib/stores/warmup-plans";

function buildProjectedVolumes(
  start: number,
  max: number,
  increment: number,
  points = 7
) {
  return Array.from({ length: points }, (_, index) =>
    Math.min(max, Math.round(start * (1 + increment / 100) ** index))
  );
}

function Sparkline({ values }: { values: number[] }) {
  if (values.length === 0) {
    return null;
  }

  const max = Math.max(...values, 1);
  const points = values
    .map((value, index) => {
      const x = (index / Math.max(values.length - 1, 1)) * 100;
      const y = 100 - (value / max) * 100;
      return `${x},${y}`;
    })
    .join(" ");

  return (
    <svg
      viewBox="0 0 100 100"
      className="h-20 w-full overflow-visible rounded-md bg-muted/40 p-2"
      preserveAspectRatio="none"
    >
      <polyline
        fill="none"
        stroke="currentColor"
        strokeWidth="3"
        points={points}
        className="text-primary"
      />
    </svg>
  );
}

export default function WarmupPage() {
  const {
    plans,
    currentPlan,
    fetchPlans,
    createPlan,
    pausePlan,
    resumePlan,
    isLoading,
  } = useWarmupPlansStore();

  useEffect(() => {
    fetchPlans().catch(() => {
      toast.error("Unable to load warm-up plans");
    });
  }, [fetchPlans]);

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-3xl font-bold">Warm-up IP</h1>
        <p className="text-sm text-muted-foreground">
          Configure a gradual sending ramp-up for email campaigns.
        </p>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Create or replace active plan</CardTitle>
        </CardHeader>
        <CardContent>
          <WarmupPlanForm
            onSubmit={async (values) => {
              await createPlan(values);
              toast.success("Warm-up plan saved");
            }}
          />
        </CardContent>
      </Card>

      {currentPlan ? (
        <Card>
          <CardHeader>
            <CardTitle>Current plan</CardTitle>
          </CardHeader>
          <CardContent className="space-y-4">
            <div className="grid gap-4 md:grid-cols-4">
              <div>
                <p className="text-xs uppercase tracking-wide text-muted-foreground">
                  Name
                </p>
                <p className="text-lg font-semibold">{currentPlan.name}</p>
              </div>
              <div>
                <p className="text-xs uppercase tracking-wide text-muted-foreground">
                  Status
                </p>
                <p className="text-lg font-semibold">{currentPlan.status}</p>
              </div>
              <div>
                <p className="text-xs uppercase tracking-wide text-muted-foreground">
                  Day
                </p>
                <p className="text-lg font-semibold">
                  {currentPlan.current_day}
                </p>
              </div>
              <div>
                <p className="text-xs uppercase tracking-wide text-muted-foreground">
                  Limit today
                </p>
                <p className="text-lg font-semibold">
                  {currentPlan.current_daily_limit}
                </p>
              </div>
            </div>
            <div className="grid gap-4 lg:grid-cols-[1.5fr_1fr]">
              <div className="space-y-2">
                <p className="text-sm font-medium">Projected progression</p>
                <Sparkline
                  values={buildProjectedVolumes(
                    currentPlan.daily_volume_start,
                    currentPlan.daily_volume_max,
                    currentPlan.increment_percent
                  )}
                />
              </div>
              <div className="space-y-2 rounded-lg border p-4">
                <p className="text-xs uppercase tracking-wide text-muted-foreground">
                  Estimated target horizon
                </p>
                <p className="text-2xl font-semibold">
                  {Math.max(1, currentPlan.current_day + 1)} days+
                </p>
                <p className="text-sm text-muted-foreground">
                  Start {currentPlan.daily_volume_start} · Max{" "}
                  {currentPlan.daily_volume_max} · +
                  {currentPlan.increment_percent}
                  %/day
                </p>
              </div>
            </div>
            <div className="flex gap-3">
              {currentPlan.status === "active" ? (
                <Button
                  variant="outline"
                  onClick={() => pausePlan(currentPlan.id)}
                  disabled={isLoading}
                >
                  Pause
                </Button>
              ) : (
                <Button
                  onClick={() => resumePlan(currentPlan.id)}
                  disabled={isLoading}
                >
                  Resume
                </Button>
              )}
            </div>
          </CardContent>
        </Card>
      ) : null}

      <Card>
        <CardHeader>
          <CardTitle>All plans</CardTitle>
        </CardHeader>
        <CardContent>
          <div className="space-y-3">
            {plans.map((plan) => (
              <div
                key={plan.id}
                className="flex items-center justify-between rounded-lg border p-4"
              >
                <div>
                  <p className="font-medium">{plan.name}</p>
                  <p className="text-sm text-muted-foreground">
                    {plan.status} · day {plan.current_day} · limit{" "}
                    {plan.current_daily_limit}
                  </p>
                </div>
              </div>
            ))}
            {plans.length === 0 ? (
              <p className="text-sm text-muted-foreground">
                No warm-up plan configured yet.
              </p>
            ) : null}
          </div>
        </CardContent>
      </Card>
    </div>
  );
}

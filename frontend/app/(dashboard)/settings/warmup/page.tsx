"use client";

import { useEffect } from "react";
import { toast } from "sonner";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { WarmupPlanForm } from "@/components/settings/warmup-plan-form";
import { useWarmupPlansStore } from "@/lib/stores/warmup-plans";

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

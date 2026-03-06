"use client";

import { useEffect } from "react";
import { toast } from "sonner";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { useScoringRuleStore } from "@/lib/stores/scoring-rules";

const DEFAULT_RULES = [
  { event: "campaign_sent", points: 1, expiry_days: 180, is_active: true },
  { event: "email_bounced", points: -5, expiry_days: null, is_active: true },
  { event: "email_clicked", points: 20, expiry_days: 90, is_active: true },
  { event: "email_opened", points: 10, expiry_days: 90, is_active: true },
  {
    event: "email_unsubscribed",
    points: -50,
    expiry_days: null,
    is_active: true,
  },
];

export default function ScoringSettingsPage() {
  const { rules, isLoading, fetchRules, createRule, updateRule } =
    useScoringRuleStore();

  useEffect(() => {
    fetchRules().catch(() => {
      toast.error("Unable to load scoring rules");
    });
  }, [fetchRules]);

  const saveDefaults = async () => {
    try {
      if (rules.length === 0) {
        for (const rule of DEFAULT_RULES) {
          await createRule(rule);
        }
      } else {
        for (const rule of rules) {
          const defaults = DEFAULT_RULES.find((item) => item.event === rule.event);
          if (!defaults) continue;
          await updateRule(rule.id, {
            points: defaults.points,
            expiry_days: defaults.expiry_days,
            is_active: defaults.is_active,
          });
        }
      }

      toast.success("Scoring rules reset");
    } catch {
      toast.error("Unable to reset scoring rules");
    }
  };

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-3xl font-bold">Scoring</h1>
        <p className="text-sm text-muted-foreground">
          Configure how email engagement updates contact scores.
        </p>
      </div>

      <Card>
        <CardHeader className="flex flex-row items-center justify-between">
          <CardTitle>Email scoring rules</CardTitle>
          <Button variant="outline" onClick={saveDefaults} disabled={isLoading}>
            Reset defaults
          </Button>
        </CardHeader>
        <CardContent className="space-y-4">
          {rules.map((rule) => (
            <div
              key={rule.id}
              className="grid gap-4 rounded-lg border p-4 md:grid-cols-4"
            >
              <div className="space-y-2">
                <Label>Event</Label>
                <Input value={rule.event} disabled />
              </div>
              <div className="space-y-2">
                <Label>Points</Label>
                <Input
                  type="number"
                  value={rule.points}
                  onChange={(event) => {
                    const nextPoints = Number(event.target.value || 0);
                    void updateRule(rule.id, { points: nextPoints }).catch(() => {
                      toast.error("Unable to update rule");
                    });
                  }}
                />
              </div>
              <div className="space-y-2">
                <Label>Expiry (days)</Label>
                <Input
                  type="number"
                  value={rule.expiry_days ?? ""}
                  placeholder="Never"
                  onChange={(event) => {
                    const raw = event.target.value.trim();
                    void updateRule(rule.id, {
                      expiry_days: raw === "" ? null : Number(raw),
                    }).catch(() => {
                      toast.error("Unable to update rule");
                    });
                  }}
                />
              </div>
              <div className="space-y-2">
                <Label>Status</Label>
                <select
                  value={rule.is_active ? "active" : "inactive"}
                  onChange={(event) => {
                    void updateRule(rule.id, {
                      is_active: event.target.value === "active",
                    }).catch(() => {
                      toast.error("Unable to update rule");
                    });
                  }}
                  className="h-9 w-full rounded-md border border-input bg-background px-3 text-sm"
                >
                  <option value="active">Active</option>
                  <option value="inactive">Inactive</option>
                </select>
              </div>
            </div>
          ))}

          {rules.length === 0 ? (
            <p className="text-sm text-muted-foreground">
              No rules found yet. Reset defaults to bootstrap the scoring model.
            </p>
          ) : null}
        </CardContent>
      </Card>
    </div>
  );
}

"use client";

import { Label } from "@/components/ui/label";
import { Input } from "@/components/ui/input";
import { Textarea } from "@/components/ui/textarea";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import type { CampaignVariant } from "@/lib/stores/campaigns";

interface AbTestConfigProps {
  enabled: boolean;
  onToggle: (enabled: boolean) => void;
  variants: CampaignVariant[];
  onChangeVariant: (
    label: "A" | "B",
    field: "subject" | "content" | "send_percent",
    value: string | number
  ) => void;
  winnerCriteria: "open_rate" | "click_rate" | "manual";
  onWinnerCriteriaChange: (
    value: "open_rate" | "click_rate" | "manual"
  ) => void;
  autoSelectAfterHours: number | null;
  onAutoSelectAfterHoursChange: (value: number | null) => void;
  onFocusField?: (fieldId: string) => void;
}

export function AbTestConfig({
  enabled,
  onToggle,
  variants,
  onChangeVariant,
  winnerCriteria,
  onWinnerCriteriaChange,
  autoSelectAfterHours,
  onAutoSelectAfterHoursChange,
  onFocusField,
}: AbTestConfigProps) {
  const variantA =
    variants.find((variant) => variant.label === "A") || variants[0];
  const variantB =
    variants.find((variant) => variant.label === "B") || variants[1];

  const splitA = Number(variantA?.send_percent || 50);
  const splitB = Number(variantB?.send_percent || 50);
  const splitValid = splitA + splitB === 100;

  return (
    <Card>
      <CardHeader>
        <CardTitle className="text-base">A/B Testing</CardTitle>
      </CardHeader>
      <CardContent className="space-y-4">
        <label className="flex items-center gap-2 text-sm font-medium">
          <input
            type="checkbox"
            checked={enabled}
            onChange={(event) => onToggle(event.target.checked)}
          />
          Activer le A/B test
        </label>

        {!enabled ? null : (
          <>
            <div className="grid gap-4 md:grid-cols-2">
              {[variantA, variantB].map((variant) => (
                <div
                  key={variant?.label}
                  className="space-y-2 rounded-md border p-3"
                >
                  <p className="text-sm font-semibold">
                    Variante {variant?.label}
                  </p>
                  <div className="space-y-1">
                    <Label htmlFor={`variant-subject-${variant?.label}`}>
                      Sujet
                    </Label>
                    <Input
                      id={`variant-subject-${variant?.label}`}
                      value={variant?.subject || ""}
                      onFocus={() =>
                        onFocusField?.(`variant-${variant?.label}-subject`)
                      }
                      onChange={(event) =>
                        onChangeVariant(
                          (variant?.label || "A") as "A" | "B",
                          "subject",
                          event.target.value
                        )
                      }
                    />
                  </div>
                  <div className="space-y-1">
                    <Label htmlFor={`variant-content-${variant?.label}`}>
                      Contenu
                    </Label>
                    <Textarea
                      id={`variant-content-${variant?.label}`}
                      rows={6}
                      value={variant?.content || ""}
                      onFocus={() =>
                        onFocusField?.(`variant-${variant?.label}-content`)
                      }
                      onChange={(event) =>
                        onChangeVariant(
                          (variant?.label || "A") as "A" | "B",
                          "content",
                          event.target.value
                        )
                      }
                    />
                  </div>
                </div>
              ))}
            </div>

            <div className="grid gap-3 md:grid-cols-2">
              <div className="space-y-1">
                <Label htmlFor="split-a">Répartition A (%)</Label>
                <Input
                  id="split-a"
                  type="number"
                  min={1}
                  max={99}
                  value={splitA}
                  onChange={(event) =>
                    onChangeVariant(
                      "A",
                      "send_percent",
                      Number(event.target.value || 0)
                    )
                  }
                />
              </div>
              <div className="space-y-1">
                <Label htmlFor="split-b">Répartition B (%)</Label>
                <Input
                  id="split-b"
                  type="number"
                  min={1}
                  max={99}
                  value={splitB}
                  onChange={(event) =>
                    onChangeVariant(
                      "B",
                      "send_percent",
                      Number(event.target.value || 0)
                    )
                  }
                />
              </div>
            </div>

            <p
              className={`text-sm ${splitValid ? "text-muted-foreground" : "text-red-600"}`}
            >
              Total split: {splitA + splitB}%{" "}
              {splitValid ? "" : "(doit être égal à 100%)"}
            </p>

            <div className="space-y-1">
              <Label htmlFor="winner-criteria">Critère gagnant</Label>
              <select
                id="winner-criteria"
                value={winnerCriteria}
                onChange={(event) =>
                  onWinnerCriteriaChange(
                    event.target.value as "open_rate" | "click_rate" | "manual"
                  )
                }
                className="h-9 w-full rounded-md border border-input bg-background px-3 text-sm"
              >
                <option value="open_rate">open_rate</option>
                <option value="click_rate">click_rate</option>
                <option value="manual">manual</option>
              </select>
            </div>

            {winnerCriteria === "manual" ? null : (
              <div className="space-y-1">
                <Label htmlFor="auto-select-hours">
                  Sélection automatique après N heures
                </Label>
                <Input
                  id="auto-select-hours"
                  type="number"
                  min={1}
                  max={72}
                  value={autoSelectAfterHours ?? 24}
                  onChange={(event) =>
                    onAutoSelectAfterHoursChange(
                      Number(event.target.value || 0)
                    )
                  }
                />
              </div>
            )}
          </>
        )}
      </CardContent>
    </Card>
  );
}

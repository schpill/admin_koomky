"use client";

import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import type {
  Campaign,
  CampaignAbVariantAnalytics,
} from "@/lib/stores/campaigns";

interface AbTestResultsProps {
  campaign: Campaign;
  variants: CampaignAbVariantAnalytics[];
  isSubmitting?: boolean;
  onSelectWinner?: (label: "A" | "B") => Promise<void>;
}

export function AbTestResults({
  campaign,
  variants,
  isSubmitting = false,
  onSelectWinner,
}: AbTestResultsProps) {
  return (
    <Card>
      <CardHeader>
        <CardTitle>A/B results</CardTitle>
      </CardHeader>
      <CardContent className="space-y-3">
        {variants.map((variant) => (
          <div
            key={variant.label}
            className="rounded-md border p-3"
            data-testid={`ab-variant-${variant.label}`}
          >
            <div className="mb-2 flex items-center justify-between">
              <p className="text-sm font-semibold">Variante {variant.label}</p>
              {variant.is_winner ? <Badge>Gagnant</Badge> : null}
            </div>
            <div className="grid gap-2 text-sm md:grid-cols-3">
              <p>Envoyés: {variant.sent_count}</p>
              <p>
                Ouverts: {variant.open_count} ({variant.open_rate}%)
              </p>
              <p>
                Clics: {variant.click_count} ({variant.click_rate}%)
              </p>
            </div>

            {campaign.ab_winner_criteria === "manual" &&
            !campaign.ab_winner_variant_id &&
            !variant.is_winner &&
            onSelectWinner ? (
              <Button
                type="button"
                variant="outline"
                size="sm"
                disabled={isSubmitting}
                className="mt-3"
                onClick={() => onSelectWinner(variant.label)}
              >
                Sélectionner comme gagnant
              </Button>
            ) : null}
          </div>
        ))}
      </CardContent>
    </Card>
  );
}

"use client";

import { useState } from "react";
import { useRouter } from "next/navigation";
import { toast } from "sonner";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { SegmentBuilder } from "@/components/segments/segment-builder";
import { SegmentPreviewPanel } from "@/components/segments/segment-preview-panel";
import { useSegmentStore, type SegmentFilters } from "@/lib/stores/segments";
import { useI18n } from "@/components/providers/i18n-provider";

const defaultFilters: SegmentFilters = {
  group_boolean: "and",
  criteria_boolean: "or",
  groups: [
    {
      criteria: [{ type: "tag", operator: "equals", value: "" }],
    },
  ],
};

export default function CreateSegmentPage() {
  const { t } = useI18n();
  const router = useRouter();
  const { createSegment, isLoading } = useSegmentStore();

  const [name, setName] = useState("");
  const [description, setDescription] = useState("");
  const [filters, setFilters] = useState<SegmentFilters>(defaultFilters);

  const preview = { contacts: [], total: 0 };

  const saveSegment = async () => {
    try {
      const segment = await createSegment({
        name,
        description: description || null,
        filters,
      });

      if (segment?.id) {
        toast.success("Segment created");
        router.push(`/campaigns/segments/${segment.id}/edit`);
      }
    } catch (error) {
      toast.error((error as Error).message || "Unable to create segment");
    }
  };

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-3xl font-bold">
          {t("campaigns.segments.create.title")}
        </h1>
        <p className="text-sm text-muted-foreground">
          {t("campaigns.segments.create.description")}
        </p>
      </div>

      <div className="space-y-4 rounded-lg border p-4">
        <div className="space-y-2">
          <Label htmlFor="segment-name">
            {t("campaigns.segments.create.name")}
          </Label>
          <Input
            id="segment-name"
            value={name}
            onChange={(event) => setName(event.target.value)}
            placeholder={t("campaigns.segments.create.namePlaceholder")}
          />
        </div>

        <div className="space-y-2">
          <Label htmlFor="segment-description">
            {t("campaigns.segments.create.descriptionLabel")}
          </Label>
          <Textarea
            id="segment-description"
            value={description}
            onChange={(event) => setDescription(event.target.value)}
            placeholder={t("campaigns.segments.create.descriptionPlaceholder")}
          />
        </div>
      </div>

      <SegmentBuilder value={filters} onChange={setFilters} previewCount={0} />

      <SegmentPreviewPanel
        contacts={preview.contacts}
        totalMatching={preview.total}
        isLoading={false}
      />

      <div className="flex justify-end">
        <Button type="button" onClick={saveSegment} disabled={isLoading}>
          {isLoading
            ? t("campaigns.segments.create.saving")
            : t("campaigns.segments.create.save")}
        </Button>
      </div>
    </div>
  );
}

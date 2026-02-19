"use client";

import { useEffect, useMemo, useState } from "react";
import Link from "next/link";
import { useParams, useRouter } from "next/navigation";
import { toast } from "sonner";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { SegmentBuilder } from "@/components/segments/segment-builder";
import { SegmentPreviewPanel } from "@/components/segments/segment-preview-panel";
import { useSegmentStore } from "@/lib/stores/segments";
import { useI18n } from "@/components/providers/i18n-provider";

export default function EditSegmentPage() {
  const { t } = useI18n();
  const params = useParams<{ id: string }>();
  const segmentId = params.id;
  const router = useRouter();
  const {
    currentSegment,
    preview,
    isLoading,
    fetchSegment,
    updateSegment,
    deleteSegment,
    previewSegment,
  } = useSegmentStore();

  const [name, setName] = useState("");
  const [description, setDescription] = useState("");
  const [filters, setFilters] = useState<any>({
    group_boolean: "and",
    criteria_boolean: "or",
    groups: [],
  });

  useEffect(() => {
    if (!segmentId) {
      return;
    }

    fetchSegment(segmentId)
      .then((segment) => {
        if (!segment) {
          return;
        }

        setName(segment.name || "");
        setDescription(segment.description || "");
        setFilters(segment.filters);

        return previewSegment(segmentId);
      })
      .catch((error) => {
        toast.error((error as Error).message || "Unable to load segment");
      });
  }, [fetchSegment, previewSegment, segmentId]);

  const contacts = useMemo(() => preview?.contacts.data || [], [preview]);

  const save = async () => {
    if (!segmentId) {
      toast.error("Missing segment id");
      return;
    }

    try {
      await updateSegment(segmentId, {
        name,
        description: description || null,
        filters,
      });

      await previewSegment(segmentId);
      toast.success("Segment updated");
    } catch (error) {
      toast.error((error as Error).message || "Unable to update segment");
    }
  };

  const remove = async () => {
    if (!segmentId) {
      toast.error("Missing segment id");
      return;
    }

    try {
      await deleteSegment(segmentId);
      toast.success("Segment deleted");
      router.push("/campaigns/segments");
    } catch (error) {
      toast.error((error as Error).message || "Unable to delete segment");
    }
  };

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold">
            {t("campaigns.segments.edit.title")}
          </h1>
          <p className="text-sm text-muted-foreground">
            {t("campaigns.segments.edit.description")}
          </p>
        </div>
        <Button variant="outline" asChild>
          <Link href="/campaigns/segments">
            {t("campaigns.segments.edit.backToList")}
          </Link>
        </Button>
      </div>

      <div className="space-y-4 rounded-lg border p-4">
        <div className="space-y-2">
          <Label htmlFor="segment-name">
            {t("campaigns.segments.edit.name")}
          </Label>
          <Input
            id="segment-name"
            value={name}
            onChange={(event) => setName(event.target.value)}
          />
        </div>

        <div className="space-y-2">
          <Label htmlFor="segment-description">
            {t("campaigns.segments.edit.descriptionLabel")}
          </Label>
          <Textarea
            id="segment-description"
            value={description}
            onChange={(event) => setDescription(event.target.value)}
          />
        </div>
      </div>

      <SegmentBuilder
        value={filters}
        onChange={setFilters}
        previewCount={
          preview?.total_matching || currentSegment?.contact_count || 0
        }
        onPreview={() =>
          segmentId
            ? previewSegment(segmentId).catch(() => undefined)
            : Promise.resolve()
        }
      />

      <SegmentPreviewPanel
        contacts={contacts}
        totalMatching={preview?.total_matching || 0}
        isLoading={isLoading}
      />

      <div className="flex justify-between gap-2">
        <Button variant="destructive" onClick={remove} disabled={isLoading}>
          {t("campaigns.segments.edit.delete")}
        </Button>
        <Button onClick={save} disabled={!name || isLoading}>
          {isLoading
            ? t("campaigns.segments.edit.saving")
            : t("campaigns.segments.edit.save")}
        </Button>
      </div>
    </div>
  );
}

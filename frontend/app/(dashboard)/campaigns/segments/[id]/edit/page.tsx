"use client";

import { useEffect, useMemo, useState } from "react";
import Link from "next/link";
import { useRouter } from "next/navigation";
import { toast } from "sonner";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { SegmentBuilder } from "@/components/segments/segment-builder";
import { SegmentPreviewPanel } from "@/components/segments/segment-preview-panel";
import { useSegmentStore } from "@/lib/stores/segments";

interface EditSegmentPageProps {
  params: {
    id: string;
  };
}

export default function EditSegmentPage({ params }: EditSegmentPageProps) {
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
    fetchSegment(params.id)
      .then((segment) => {
        if (!segment) {
          return;
        }

        setName(segment.name || "");
        setDescription(segment.description || "");
        setFilters(segment.filters);

        return previewSegment(params.id);
      })
      .catch((error) => {
        toast.error((error as Error).message || "Unable to load segment");
      });
  }, [fetchSegment, previewSegment, params.id]);

  const contacts = useMemo(() => preview?.contacts.data || [], [preview]);

  const save = async () => {
    try {
      await updateSegment(params.id, {
        name,
        description: description || null,
        filters,
      });

      await previewSegment(params.id);
      toast.success("Segment updated");
    } catch (error) {
      toast.error((error as Error).message || "Unable to update segment");
    }
  };

  const remove = async () => {
    try {
      await deleteSegment(params.id);
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
          <h1 className="text-3xl font-bold">Edit segment</h1>
          <p className="text-sm text-muted-foreground">
            Update filters and refresh matching contacts.
          </p>
        </div>
        <Button variant="outline" asChild>
          <Link href="/campaigns/segments">Back to list</Link>
        </Button>
      </div>

      <div className="space-y-4 rounded-lg border p-4">
        <div className="space-y-2">
          <Label htmlFor="segment-name">Name</Label>
          <Input
            id="segment-name"
            value={name}
            onChange={(event) => setName(event.target.value)}
          />
        </div>

        <div className="space-y-2">
          <Label htmlFor="segment-description">Description</Label>
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
        onPreview={() => previewSegment(params.id).catch(() => undefined)}
      />

      <SegmentPreviewPanel
        contacts={contacts}
        totalMatching={preview?.total_matching || 0}
        isLoading={isLoading}
      />

      <div className="flex justify-between gap-2">
        <Button variant="destructive" onClick={remove} disabled={isLoading}>
          Delete segment
        </Button>
        <Button onClick={save} disabled={!name || isLoading}>
          {isLoading ? "Saving..." : "Save changes"}
        </Button>
      </div>
    </div>
  );
}

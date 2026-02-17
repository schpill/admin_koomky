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
import { useSegmentStore } from "@/lib/stores/segments";

const defaultFilters = {
  group_boolean: "and" as const,
  criteria_boolean: "or" as const,
  groups: [
    {
      criteria: [{ type: "tag", operator: "equals", value: "" }],
    },
  ],
};

export default function CreateSegmentPage() {
  const router = useRouter();
  const { createSegment, isLoading } = useSegmentStore();

  const [name, setName] = useState("");
  const [description, setDescription] = useState("");
  const [filters, setFilters] = useState(defaultFilters);

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
        <h1 className="text-3xl font-bold">Create segment</h1>
        <p className="text-sm text-muted-foreground">
          Build your audience with visual filter groups.
        </p>
      </div>

      <div className="space-y-4 rounded-lg border p-4">
        <div className="space-y-2">
          <Label htmlFor="segment-name">Name</Label>
          <Input
            id="segment-name"
            value={name}
            onChange={(event) => setName(event.target.value)}
            placeholder="VIP clients"
          />
        </div>

        <div className="space-y-2">
          <Label htmlFor="segment-description">Description</Label>
          <Textarea
            id="segment-description"
            value={description}
            onChange={(event) => setDescription(event.target.value)}
            placeholder="Contacts with high engagement"
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
          {isLoading ? "Saving..." : "Save segment"}
        </Button>
      </div>
    </div>
  );
}

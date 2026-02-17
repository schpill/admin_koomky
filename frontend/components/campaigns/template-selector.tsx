"use client";

import type { CampaignTemplate } from "@/lib/stores/campaigns";
import { Label } from "@/components/ui/label";

interface TemplateSelectorProps {
  templates: CampaignTemplate[];
  selectedTemplateId?: string;
  onSelect: (templateId: string) => void;
}

export function TemplateSelector({
  templates,
  selectedTemplateId,
  onSelect,
}: TemplateSelectorProps) {
  return (
    <div className="space-y-2">
      <Label htmlFor="campaign-template">Template</Label>
      <select
        id="campaign-template"
        value={selectedTemplateId || ""}
        onChange={(event) => onSelect(event.target.value)}
        className="h-9 w-full rounded-md border border-input bg-background px-3 text-sm"
      >
        <option value="">Start from blank</option>
        {templates.map((template) => (
          <option key={template.id} value={template.id}>
            {template.name}
          </option>
        ))}
      </select>
    </div>
  );
}

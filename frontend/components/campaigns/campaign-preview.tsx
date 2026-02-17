"use client";

import { useMemo, useState } from "react";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Label } from "@/components/ui/label";

export interface CampaignPreviewRecipient {
  id: string;
  first_name: string;
  last_name?: string | null;
  email?: string | null;
  company?: string | null;
}

interface CampaignPreviewProps {
  subject?: string | null;
  content: string;
  recipients: CampaignPreviewRecipient[];
}

function personalize(
  template: string,
  recipient: CampaignPreviewRecipient
): string {
  return template
    .replaceAll("{{first_name}}", recipient.first_name || "")
    .replaceAll("{{last_name}}", recipient.last_name || "")
    .replaceAll("{{company}}", recipient.company || "")
    .replaceAll("{{email}}", recipient.email || "");
}

export function CampaignPreview({
  subject,
  content,
  recipients,
}: CampaignPreviewProps) {
  const [recipientId, setRecipientId] = useState(recipients[0]?.id || "");

  const recipient = useMemo(
    () => recipients.find((item) => item.id === recipientId) || recipients[0],
    [recipientId, recipients]
  );

  if (!recipient) {
    return (
      <Card>
        <CardHeader>
          <CardTitle>Campaign preview</CardTitle>
        </CardHeader>
        <CardContent>
          <p className="text-sm text-muted-foreground">
            No sample recipient available.
          </p>
        </CardContent>
      </Card>
    );
  }

  return (
    <Card>
      <CardHeader>
        <CardTitle>Campaign preview</CardTitle>
      </CardHeader>
      <CardContent className="space-y-4">
        <div className="space-y-1">
          <Label htmlFor="preview-recipient">Preview recipient</Label>
          <select
            id="preview-recipient"
            aria-label="Preview recipient"
            value={recipient.id}
            onChange={(event) => setRecipientId(event.target.value)}
            className="h-9 w-full rounded-md border border-input bg-background px-3 text-sm"
          >
            {recipients.map((item) => (
              <option key={item.id} value={item.id}>
                {item.first_name} {item.last_name || ""}
              </option>
            ))}
          </select>
        </div>

        <div className="rounded-md border bg-muted/20 p-4">
          <p className="text-sm font-semibold">
            {personalize(subject || "(No subject)", recipient)}
          </p>
          <p className="mt-3 whitespace-pre-wrap text-sm">
            {personalize(content, recipient)}
          </p>
        </div>
      </CardContent>
    </Card>
  );
}

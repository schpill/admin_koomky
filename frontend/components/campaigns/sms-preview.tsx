"use client";

import type { CampaignPreviewRecipient } from "@/components/campaigns/campaign-preview";

interface SmsPreviewProps {
  content: string;
  recipient: Omit<CampaignPreviewRecipient, "id">;
}

function personalize(content: string, recipient: Omit<CampaignPreviewRecipient, "id">): string {
  return content
    .replaceAll("{{first_name}}", recipient.first_name || "")
    .replaceAll("{{last_name}}", recipient.last_name || "")
    .replaceAll("{{company}}", recipient.company || "")
    .replaceAll("{{email}}", recipient.email || "");
}

export function SmsPreview({ content, recipient }: SmsPreviewProps) {
  return (
    <div className="mx-auto w-full max-w-xs rounded-[2rem] border-8 border-neutral-900 bg-neutral-900 p-3 shadow-xl">
      <div className="rounded-[1.4rem] bg-neutral-100 p-3">
        <div className="rounded-2xl bg-primary px-4 py-3 text-sm text-primary-foreground">
          {personalize(content, recipient)}
        </div>
      </div>
    </div>
  );
}

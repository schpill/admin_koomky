"use client";
import { useState } from "react";
import { Button } from "@/components/ui/button";
import { Textarea } from "@/components/ui/textarea";
import { Label } from "@/components/ui/label";
import { Checkbox } from "@/components/ui/checkbox";
import { useI18n } from "@/components/providers/i18n-provider";

interface TicketMessageComposerProps {
  currentUserId: string;
  isOwnerOrAssignee: boolean;
  onSubmit: (data: { content: string; is_internal: boolean }) => void;
  isLoading?: boolean;
}

export function TicketMessageComposer({
  currentUserId,
  isOwnerOrAssignee,
  onSubmit,
  isLoading,
}: TicketMessageComposerProps) {
  const { t } = useI18n();
  const [content, setContent] = useState("");
  const [isInternal, setIsInternal] = useState(false);

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (!content.trim()) return;
    onSubmit({ content: content.trim(), is_internal: isInternal });
    setContent("");
    setIsInternal(false);
  };

  return (
    <form onSubmit={handleSubmit} className="space-y-3">
      <Textarea
        value={content}
        onChange={(e) => setContent(e.target.value)}
        placeholder={t("tickets.message.placeholder")}
        rows={3}
      />
      <div className="flex items-center justify-between">
        {isOwnerOrAssignee && (
          <div className="flex items-center gap-2">
            <Checkbox
              id="is-internal"
              checked={isInternal}
              onCheckedChange={(v) => setIsInternal(!!v)}
            />
            <Label htmlFor="is-internal" className="text-sm">
              {t("tickets.message.internalNote")}
            </Label>
          </div>
        )}
        <Button
          type="submit"
          disabled={!content.trim() || isLoading}
          className="ml-auto"
        >
          {isLoading ? t("tickets.message.sending") : t("tickets.message.send")}
        </Button>
      </div>
    </form>
  );
}

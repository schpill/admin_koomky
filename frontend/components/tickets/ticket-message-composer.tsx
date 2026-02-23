"use client";
import { useState } from "react";
import { Button } from "@/components/ui/button";
import { Textarea } from "@/components/ui/textarea";
import { Label } from "@/components/ui/label";
import { Checkbox } from "@/components/ui/checkbox";

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
        placeholder="Write a message..."
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
              Internal note
            </Label>
          </div>
        )}
        <Button
          type="submit"
          disabled={!content.trim() || isLoading}
          className="ml-auto"
        >
          {isLoading ? "Sending..." : "Send"}
        </Button>
      </div>
    </form>
  );
}

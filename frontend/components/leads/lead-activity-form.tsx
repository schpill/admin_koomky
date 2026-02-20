"use client";

import { useState, useCallback } from "react";
import { Calendar, Mail, Phone, MessageSquare, Clock } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Textarea } from "@/components/ui/textarea";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { useLeadStore, LeadActivity } from "@/lib/stores/leads";
import { cn } from "@/lib/utils";

const ACTIVITY_TYPES: Array<{
  value: LeadActivity["type"];
  label: string;
  icon: React.ReactNode;
}> = [
  { value: "note", label: "Note", icon: <MessageSquare className="h-4 w-4" /> },
  {
    value: "email_sent",
    label: "Email Sent",
    icon: <Mail className="h-4 w-4" />,
  },
  { value: "call", label: "Call", icon: <Phone className="h-4 w-4" /> },
  {
    value: "meeting",
    label: "Meeting",
    icon: <Calendar className="h-4 w-4" />,
  },
  {
    value: "follow_up",
    label: "Follow-up",
    icon: <Clock className="h-4 w-4" />,
  },
];

interface LeadActivityFormProps {
  leadId: string;
  onSuccess?: (activity: LeadActivity) => void;
  onCancel?: () => void;
  className?: string;
  compact?: boolean;
}

export function LeadActivityForm({
  leadId,
  onSuccess,
  onCancel,
  className,
  compact = false,
}: LeadActivityFormProps) {
  const { createActivity } = useLeadStore();
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [type, setType] = useState<LeadActivity["type"]>("note");
  const [content, setContent] = useState("");
  const [scheduledAt, setScheduledAt] = useState("");

  const resetForm = useCallback(() => {
    setType("note");
    setContent("");
    setScheduledAt("");
  }, []);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();

    if (!content.trim()) {
      return;
    }

    setIsSubmitting(true);

    try {
      const payload: Record<string, unknown> = {
        type,
        content: content.trim(),
      };

      if (type === "follow_up" && scheduledAt) {
        payload.scheduled_at = scheduledAt;
      }

      const activity = await createActivity(leadId, payload);

      if (activity) {
        resetForm();
        onSuccess?.(activity);
      }
    } catch (error) {
      console.error("Failed to create activity:", error);
    } finally {
      setIsSubmitting(false);
    }
  };

  const handleKeyDown = (e: React.KeyboardEvent<HTMLTextAreaElement>) => {
    if (e.key === "Enter" && !e.shiftKey) {
      e.preventDefault();
      handleSubmit(e);
    }
  };

  const selectedType = ACTIVITY_TYPES.find((t) => t.value === type);

  return (
    <form onSubmit={handleSubmit} className={cn("space-y-3", className)}>
      <div
        className={cn("grid gap-3", compact ? "grid-cols-1" : "grid-cols-2")}
      >
        <div className="space-y-1.5">
          <Label htmlFor="activity-type" className="text-sm">
            Activity Type
          </Label>
          <Select
            value={type}
            onValueChange={(value) => setType(value as LeadActivity["type"])}
          >
            <SelectTrigger id="activity-type">
              <SelectValue>
                <div className="flex items-center gap-2">
                  {selectedType?.icon}
                  <span>{selectedType?.label}</span>
                </div>
              </SelectValue>
            </SelectTrigger>
            <SelectContent>
              {ACTIVITY_TYPES.map((activityType) => (
                <SelectItem key={activityType.value} value={activityType.value}>
                  <div className="flex items-center gap-2">
                    {activityType.icon}
                    <span>{activityType.label}</span>
                  </div>
                </SelectItem>
              ))}
            </SelectContent>
          </Select>
        </div>

        {type === "follow_up" && (
          <div className="space-y-1.5">
            <Label htmlFor="scheduled-at" className="text-sm">
              Scheduled At
            </Label>
            <Input
              id="scheduled-at"
              type="datetime-local"
              value={scheduledAt}
              onChange={(e) => setScheduledAt(e.target.value)}
              className="h-9"
            />
          </div>
        )}
      </div>

      <div className="space-y-1.5">
        <Label htmlFor="content" className="text-sm">
          Content
        </Label>
        <Textarea
          id="content"
          placeholder={
            type === "note"
              ? "Add a note..."
              : type === "call"
                ? "Call summary..."
                : type === "email_sent"
                  ? "Email details..."
                  : type === "meeting"
                    ? "Meeting notes..."
                    : "Follow-up details..."
          }
          value={content}
          onChange={(e) => setContent(e.target.value)}
          onKeyDown={handleKeyDown}
          className={cn(
            "resize-none",
            compact ? "min-h-[60px]" : "min-h-[80px]"
          )}
          required
        />
        <p className="text-xs text-muted-foreground">
          Press Enter to submit, Shift+Enter for new line
        </p>
      </div>

      <div className="flex items-center justify-end gap-2">
        {onCancel && (
          <Button
            type="button"
            variant="outline"
            size="sm"
            onClick={() => {
              resetForm();
              onCancel();
            }}
            disabled={isSubmitting}
          >
            Cancel
          </Button>
        )}
        <Button
          type="submit"
          size="sm"
          disabled={isSubmitting || !content.trim()}
        >
          {isSubmitting ? "Saving..." : "Log Activity"}
        </Button>
      </div>
    </form>
  );
}

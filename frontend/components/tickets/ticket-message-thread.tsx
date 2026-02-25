"use client";
import { Button } from "@/components/ui/button";
import { cn } from "@/lib/utils";
import { Pencil, Trash2 } from "lucide-react";
import { useI18n } from "@/components/providers/i18n-provider";

interface TicketMessage {
  id: string;
  ticket_id: string;
  user_id: string;
  content: string;
  is_internal: boolean;
  created_at: string;
  updated_at: string;
  user?: { id: string; name: string; email: string };
}

interface TicketMessageThreadProps {
  messages: TicketMessage[];
  currentUserId: string;
  isOwnerOrAssignee: boolean;
  onEdit: (msg: TicketMessage) => void;
  onDelete: (msgId: string) => void;
}

function formatDate(dateStr: string): string {
  try {
    return new Date(dateStr).toLocaleString();
  } catch {
    return dateStr;
  }
}

function getInitials(name?: string): string {
  if (!name) return "?";
  return name
    .split(" ")
    .map((n) => n[0])
    .join("")
    .toUpperCase()
    .slice(0, 2);
}

export function TicketMessageThread({
  messages,
  currentUserId,
  isOwnerOrAssignee,
  onEdit,
  onDelete,
}: TicketMessageThreadProps) {
  const { t } = useI18n();
  const visibleMessages = messages.filter(
    (m) => !m.is_internal || isOwnerOrAssignee
  );

  if (visibleMessages.length === 0) {
    return (
      <p className="text-sm text-muted-foreground py-4 text-center">
        {t("tickets.message.noMessages")}
      </p>
    );
  }

  return (
    <div className="space-y-4">
      {visibleMessages.map((message) => (
        <div
          key={message.id}
          className={cn(
            "rounded-lg border p-4",
            message.is_internal ? "border-yellow-200 bg-yellow-50" : "bg-white"
          )}
        >
          <div className="flex items-start justify-between gap-2">
            <div className="flex items-center gap-2">
              <div className="flex h-8 w-8 items-center justify-center rounded-full bg-primary text-xs font-medium text-primary-foreground">
                {getInitials(message.user?.name)}
              </div>
              <div>
                <p className="text-sm font-medium">
                  {message.user?.name ?? t("tickets.message.unknown")}
                </p>
                <p className="text-xs text-muted-foreground">
                  {formatDate(message.created_at)}
                </p>
              </div>
              {message.is_internal && (
                <span className="rounded-full bg-yellow-200 px-2 py-0.5 text-xs font-medium text-yellow-800">
                  {t("tickets.message.internalLabel")}
                </span>
              )}
            </div>
            {message.user_id === currentUserId && (
              <div className="flex gap-1">
                <Button
                  variant="ghost"
                  size="sm"
                  onClick={() => onEdit(message)}
                  aria-label="Edit message"
                >
                  <Pencil className="h-3.5 w-3.5" />
                </Button>
                <Button
                  variant="ghost"
                  size="sm"
                  onClick={() => onDelete(message.id)}
                  aria-label="Delete message"
                >
                  <Trash2 className="h-3.5 w-3.5" />
                </Button>
              </div>
            )}
          </div>
          <p className="mt-2 text-sm whitespace-pre-wrap">{message.content}</p>
        </div>
      ))}
    </div>
  );
}

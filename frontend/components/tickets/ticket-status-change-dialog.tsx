"use client";
import { useState } from "react";
import {
  Dialog,
  DialogContent,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import { Button } from "@/components/ui/button";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { Textarea } from "@/components/ui/textarea";
import { Label } from "@/components/ui/label";
import { useI18n } from "@/components/providers/i18n-provider";

type TicketStatus = "open" | "in_progress" | "pending" | "resolved" | "closed";

interface Ticket {
  id: string;
  status: TicketStatus;
  [key: string]: any;
}

const STATUS_TRANSITIONS: Record<TicketStatus, TicketStatus[]> = {
  open: ["in_progress", "pending", "resolved", "closed"],
  in_progress: ["open", "pending", "resolved", "closed"],
  pending: ["open", "in_progress", "resolved", "closed"],
  resolved: ["open", "closed"],
  closed: ["open"],
};

interface TicketStatusChangeDialogProps {
  ticket: Ticket;
  open: boolean;
  onOpenChange: (open: boolean) => void;
  onStatusChange: (status: TicketStatus, comment?: string) => void;
}

export function TicketStatusChangeDialog({
  ticket,
  open,
  onOpenChange,
  onStatusChange,
}: TicketStatusChangeDialogProps) {
  const { t } = useI18n();
  const validTransitions = STATUS_TRANSITIONS[ticket.status] ?? [];
  const [newStatus, setNewStatus] = useState<TicketStatus>(validTransitions[0]);
  const [comment, setComment] = useState("");

  const handleSubmit = () => {
    if (!newStatus) return;
    onStatusChange(newStatus, comment || undefined);
    onOpenChange(false);
    setComment("");
  };

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>{t("tickets.statusChange.title")}</DialogTitle>
        </DialogHeader>
        <div className="space-y-4 py-2">
          <div className="space-y-2">
            <Label>{t("tickets.statusChange.newStatus")}</Label>
            <Select
              value={newStatus}
              onValueChange={(v) => setNewStatus(v as TicketStatus)}
            >
              <SelectTrigger>
                <SelectValue
                  placeholder={t("tickets.statusChange.selectStatus")}
                />
              </SelectTrigger>
              <SelectContent>
                {validTransitions.map((s) => (
                  <SelectItem key={s} value={s}>
                    {t(`tickets.status.${s}`)}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
          </div>
          <div className="space-y-2">
            <Label>{t("tickets.statusChange.comment")}</Label>
            <Textarea
              placeholder={t("tickets.statusChange.commentPlaceholder")}
              value={comment}
              onChange={(e) => setComment(e.target.value)}
              rows={3}
            />
          </div>
        </div>
        <DialogFooter>
          <Button variant="outline" onClick={() => onOpenChange(false)}>
            {t("tickets.statusChange.cancel")}
          </Button>
          <Button onClick={handleSubmit} disabled={!newStatus}>
            {t("tickets.statusChange.confirm")}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

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

const STATUS_LABELS: Record<TicketStatus, string> = {
  open: "Open",
  in_progress: "In Progress",
  pending: "Pending",
  resolved: "Resolved",
  closed: "Closed",
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
          <DialogTitle>Change Status</DialogTitle>
        </DialogHeader>
        <div className="space-y-4 py-2">
          <div className="space-y-2">
            <Label>New status</Label>
            <Select
              value={newStatus}
              onValueChange={(v) => setNewStatus(v as TicketStatus)}
            >
              <SelectTrigger>
                <SelectValue placeholder="Select status" />
              </SelectTrigger>
              <SelectContent>
                {validTransitions.map((s) => (
                  <SelectItem key={s} value={s}>
                    {STATUS_LABELS[s]}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
          </div>
          <div className="space-y-2">
            <Label>Comment (optional)</Label>
            <Textarea
              placeholder="Add a comment — this will create a public message on the ticket"
              value={comment}
              onChange={(e) => setComment(e.target.value)}
              rows={3}
            />
          </div>
        </div>
        <DialogFooter>
          <Button variant="outline" onClick={() => onOpenChange(false)}>
            Cancel
          </Button>
          <Button onClick={handleSubmit} disabled={!newStatus}>
            Change Status
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

"use client";

import { Button } from "@/components/ui/button";
import {
  Popover,
  PopoverContent,
  PopoverTrigger,
} from "@/components/ui/popover";
import { type CalendarEvent } from "@/lib/stores/calendar";

interface EventDetailPopoverProps {
  event: CalendarEvent;
  trigger: React.ReactNode;
  onEdit: () => void;
  onDelete: () => void;
}

export function EventDetailPopover({
  event,
  trigger,
  onEdit,
  onDelete,
}: EventDetailPopoverProps) {
  return (
    <Popover>
      <PopoverTrigger asChild>{trigger}</PopoverTrigger>
      <PopoverContent className="space-y-3">
        <div>
          <p className="text-sm font-semibold">{event.title}</p>
          <p className="text-xs text-muted-foreground capitalize">{event.type}</p>
        </div>

        {event.description && (
          <p className="text-sm text-muted-foreground">{event.description}</p>
        )}

        <div className="text-xs text-muted-foreground">
          <p>Start: {event.start_at}</p>
          <p>End: {event.end_at}</p>
          {event.location && <p>Location: {event.location}</p>}
        </div>

        <div className="flex gap-2">
          <Button size="sm" variant="outline" onClick={onEdit}>
            Edit
          </Button>
          <Button size="sm" variant="destructive" onClick={onDelete}>
            Delete
          </Button>
        </div>
      </PopoverContent>
    </Popover>
  );
}

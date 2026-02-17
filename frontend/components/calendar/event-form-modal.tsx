"use client";

import { useEffect, useState } from "react";
import { Button } from "@/components/ui/button";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { type CalendarEventType } from "@/lib/stores/calendar";

interface EventFormModalProps {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  onSubmit: (payload: {
    title: string;
    description?: string | null;
    start_at: string;
    end_at: string;
    all_day: boolean;
    location?: string | null;
    type: CalendarEventType;
  }) => Promise<void> | void;
  submitLabel?: string;
  initialEvent?: {
    id?: string;
    title?: string;
    description?: string | null;
    start_at?: string;
    end_at?: string;
    all_day?: boolean;
    location?: string | null;
    type?: CalendarEventType;
  } | null;
}

function toInputDateTime(value?: string): string {
  if (!value) {
    return "";
  }

  const normalized = value.replace(" ", "T");
  return normalized.slice(0, 16);
}

function toApiDateTime(value: string): string {
  if (!value) {
    return "";
  }

  return `${value.replace("T", " ")}:00`;
}

export function EventFormModal({
  open,
  onOpenChange,
  onSubmit,
  submitLabel = "Save event",
  initialEvent,
}: EventFormModalProps) {
  const [title, setTitle] = useState("");
  const [description, setDescription] = useState("");
  const [startAt, setStartAt] = useState("");
  const [endAt, setEndAt] = useState("");
  const [allDay, setAllDay] = useState(false);
  const [location, setLocation] = useState("");
  const [type, setType] = useState<CalendarEventType>("meeting");

  useEffect(() => {
    if (!open) {
      return;
    }

    const now = new Date();
    const inOneHour = new Date(now.getTime() + 60 * 60 * 1000);
    const defaultStart = now.toISOString().slice(0, 16);
    const defaultEnd = inOneHour.toISOString().slice(0, 16);

    setTitle(initialEvent?.title || "");
    setDescription(initialEvent?.description || "");
    setStartAt(toInputDateTime(initialEvent?.start_at) || defaultStart);
    setEndAt(toInputDateTime(initialEvent?.end_at) || defaultEnd);
    setAllDay(Boolean(initialEvent?.all_day || false));
    setLocation(initialEvent?.location || "");
    setType(initialEvent?.type || "meeting");
  }, [initialEvent, open]);

  const handleSubmit = async () => {
    await onSubmit({
      title: title.trim(),
      description: description.trim() || null,
      start_at: toApiDateTime(startAt),
      end_at: toApiDateTime(endAt),
      all_day: allDay,
      location: location.trim() || null,
      type,
    });
    onOpenChange(false);
  };

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>
            {initialEvent?.id ? "Edit event" : "Create event"}
          </DialogTitle>
          <DialogDescription>
            Manage your meetings, reminders and deadlines.
          </DialogDescription>
        </DialogHeader>

        <div className="space-y-3">
          <div className="space-y-2">
            <Label htmlFor="calendar-event-title">Title</Label>
            <Input
              id="calendar-event-title"
              aria-label="Title"
              value={title}
              onChange={(event) => setTitle(event.target.value)}
            />
          </div>

          <div className="space-y-2">
            <Label htmlFor="calendar-event-start">Start</Label>
            <Input
              id="calendar-event-start"
              aria-label="Start"
              type="datetime-local"
              value={startAt}
              onChange={(event) => setStartAt(event.target.value)}
            />
          </div>

          <div className="space-y-2">
            <Label htmlFor="calendar-event-end">End</Label>
            <Input
              id="calendar-event-end"
              aria-label="End"
              type="datetime-local"
              value={endAt}
              onChange={(event) => setEndAt(event.target.value)}
            />
          </div>

          <div className="flex items-center gap-2">
            <input
              id="calendar-event-all-day"
              aria-label="All day"
              type="checkbox"
              checked={allDay}
              onChange={(event) => setAllDay(event.target.checked)}
            />
            <Label htmlFor="calendar-event-all-day">All day</Label>
          </div>

          <div className="space-y-2">
            <Label htmlFor="calendar-event-type">Type</Label>
            <select
              id="calendar-event-type"
              aria-label="Type"
              className="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
              value={type}
              onChange={(event) =>
                setType(event.target.value as CalendarEventType)
              }
            >
              <option value="meeting">Meeting</option>
              <option value="deadline">Deadline</option>
              <option value="reminder">Reminder</option>
              <option value="task">Task</option>
              <option value="custom">Custom</option>
            </select>
          </div>

          <div className="space-y-2">
            <Label htmlFor="calendar-event-location">Location</Label>
            <Input
              id="calendar-event-location"
              value={location}
              onChange={(event) => setLocation(event.target.value)}
            />
          </div>

          <div className="space-y-2">
            <Label htmlFor="calendar-event-description">Description</Label>
            <Textarea
              id="calendar-event-description"
              rows={3}
              value={description}
              onChange={(event) => setDescription(event.target.value)}
            />
          </div>

          <div className="flex justify-end">
            <Button type="button" onClick={handleSubmit}>
              {submitLabel}
            </Button>
          </div>
        </div>
      </DialogContent>
    </Dialog>
  );
}

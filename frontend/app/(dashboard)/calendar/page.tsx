"use client";

import { useEffect, useMemo, useState } from "react";
import { toast } from "sonner";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { EmptyState } from "@/components/ui/empty-state";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { EventDetailPopover } from "@/components/calendar/event-detail-popover";
import { EventFormModal } from "@/components/calendar/event-form-modal";
import {
  useCalendarStore,
  type CalendarEvent,
  type CalendarEventPayload,
} from "@/lib/stores/calendar";

type CalendarView = "month" | "week" | "day";

function todayIso(): string {
  return new Date().toISOString().slice(0, 10);
}

function addDays(input: string, days: number): string {
  const date = new Date(`${input}T00:00:00`);
  date.setDate(date.getDate() + days);
  return date.toISOString().slice(0, 10);
}

export default function CalendarPage() {
  const { events, isLoading, fetchEvents, createEvent, updateEvent, deleteEvent } =
    useCalendarStore();

  const [view, setView] = useState<CalendarView>("month");
  const [dateFrom, setDateFrom] = useState(todayIso());
  const [dateTo, setDateTo] = useState(addDays(todayIso(), 30));
  const [modalOpen, setModalOpen] = useState(false);
  const [editingEvent, setEditingEvent] = useState<CalendarEvent | null>(null);

  useEffect(() => {
    fetchEvents({
      date_from: dateFrom,
      date_to: dateTo,
    });
  }, [dateFrom, dateTo, fetchEvents]);

  const grouped = useMemo(() => {
    return events.reduce<Record<string, CalendarEvent[]>>((acc, event) => {
      const key = String(event.start_at).slice(0, 10);
      if (!acc[key]) {
        acc[key] = [];
      }
      acc[key].push(event);
      return acc;
    }, {});
  }, [events]);

  const sortedDays = Object.keys(grouped).sort((a, b) => a.localeCompare(b));

  const handleViewChange = (nextView: CalendarView) => {
    const base = todayIso();
    setView(nextView);

    if (nextView === "day") {
      setDateFrom(base);
      setDateTo(base);
      return;
    }

    if (nextView === "week") {
      setDateFrom(base);
      setDateTo(addDays(base, 6));
      return;
    }

    setDateFrom(base);
    setDateTo(addDays(base, 30));
  };

  const handleSubmitEvent = async (payload: CalendarEventPayload) => {
    try {
      if (editingEvent) {
        await updateEvent(editingEvent.id, payload);
        toast.success("Event updated");
      } else {
        await createEvent(payload);
        toast.success("Event created");
      }
      setEditingEvent(null);
      await fetchEvents({ date_from: dateFrom, date_to: dateTo });
    } catch (error) {
      toast.error((error as Error).message || "Unable to save event");
    }
  };

  const handleDelete = async (eventId: string) => {
    try {
      await deleteEvent(eventId);
      toast.success("Event deleted");
    } catch (error) {
      toast.error((error as Error).message || "Unable to delete event");
    }
  };

  return (
    <div className="space-y-6">
      <div className="flex flex-wrap items-center justify-between gap-3">
        <div>
          <h1 className="text-3xl font-bold">Calendar</h1>
          <p className="text-sm text-muted-foreground">
            Plan meetings, deadlines and reminders.
          </p>
        </div>
        <Button
          onClick={() => {
            setEditingEvent(null);
            setModalOpen(true);
          }}
        >
          Create event
        </Button>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>View</CardTitle>
        </CardHeader>
        <CardContent className="space-y-4">
          <div className="flex flex-wrap gap-2">
            <Button
              type="button"
              variant={view === "month" ? "default" : "outline"}
              onClick={() => handleViewChange("month")}
            >
              Month
            </Button>
            <Button
              type="button"
              variant={view === "week" ? "default" : "outline"}
              onClick={() => handleViewChange("week")}
            >
              Week
            </Button>
            <Button
              type="button"
              variant={view === "day" ? "default" : "outline"}
              onClick={() => handleViewChange("day")}
            >
              Day
            </Button>
          </div>

          <div className="grid gap-3 md:grid-cols-2">
            <div className="space-y-2">
              <Label htmlFor="calendar-date-from">From</Label>
              <Input
                id="calendar-date-from"
                type="date"
                value={dateFrom}
                onChange={(event) => setDateFrom(event.target.value)}
              />
            </div>
            <div className="space-y-2">
              <Label htmlFor="calendar-date-to">To</Label>
              <Input
                id="calendar-date-to"
                type="date"
                value={dateTo}
                onChange={(event) => setDateTo(event.target.value)}
              />
            </div>
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>Events</CardTitle>
        </CardHeader>
        <CardContent>
          {isLoading && events.length === 0 ? (
            <p className="text-sm text-muted-foreground">Loading events...</p>
          ) : events.length === 0 ? (
            <EmptyState
              title="No events"
              description="Create an event to start planning your schedule."
            />
          ) : (
            <div className="space-y-4">
              {sortedDays.map((day) => (
                <div key={day} className="space-y-2">
                  <p className="text-sm font-semibold">{day}</p>
                  <div className="grid gap-2">
                    {grouped[day].map((event) => (
                      <EventDetailPopover
                        key={event.id}
                        event={event}
                        onEdit={() => {
                          setEditingEvent(event);
                          setModalOpen(true);
                        }}
                        onDelete={() => handleDelete(event.id)}
                        trigger={
                          <button
                            className="w-full rounded-md border bg-muted/20 px-3 py-2 text-left transition hover:bg-muted/35"
                            type="button"
                          >
                            <p className="text-sm font-medium">{event.title}</p>
                            <p className="text-xs text-muted-foreground">
                              {event.start_at} - {event.end_at}
                            </p>
                          </button>
                        }
                      />
                    ))}
                  </div>
                </div>
              ))}
            </div>
          )}
        </CardContent>
      </Card>

      <EventFormModal
        open={modalOpen}
        onOpenChange={(open) => {
          setModalOpen(open);
          if (!open) {
            setEditingEvent(null);
          }
        }}
        initialEvent={editingEvent}
        submitLabel={editingEvent ? "Update event" : "Save event"}
        onSubmit={handleSubmitEvent}
      />
    </div>
  );
}

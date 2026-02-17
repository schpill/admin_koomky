import { CalendarDays } from "lucide-react";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";

interface CalendarWidgetEvent {
  id: string;
  title: string;
  start_at: string;
  type: string;
}

interface CalendarWidgetProps {
  events: CalendarWidgetEvent[];
}

export function CalendarWidget({ events }: CalendarWidgetProps) {
  return (
    <Card>
      <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
        <CardTitle className="text-base">Calendar</CardTitle>
        <CalendarDays className="h-4 w-4 text-muted-foreground" />
      </CardHeader>
      <CardContent className="space-y-2">
        {events.length === 0 ? (
          <p className="text-sm text-muted-foreground">No upcoming events.</p>
        ) : (
          events.slice(0, 5).map((event) => (
            <div
              key={event.id}
              className="rounded-md border border-border/80 bg-muted/20 px-3 py-2"
            >
              <p className="text-sm font-medium">{event.title}</p>
              <p className="text-xs text-muted-foreground">
                {event.start_at} - {event.type}
              </p>
            </div>
          ))
        )}
      </CardContent>
    </Card>
  );
}

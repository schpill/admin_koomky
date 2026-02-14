import { CalendarClock } from "lucide-react";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";

export function UpcomingDeadlinesWidget() {
  return (
    <Card className="col-span-full md:col-span-1">
      <CardHeader>
        <CardTitle className="text-lg flex items-center gap-2">
          <CalendarClock className="h-5 w-5 text-muted-foreground" />
          Upcoming Deadlines
        </CardTitle>
      </CardHeader>
      <CardContent>
        <div className="flex flex-col items-center justify-center py-8 text-center space-y-2">
          <div className="bg-muted rounded-full p-3">
            <CalendarClock className="h-6 w-6 text-muted-foreground opacity-50" />
          </div>
          <p className="text-sm font-medium">No deadlines soon</p>
          <p className="text-xs text-muted-foreground max-w-[200px]">
            Once you add projects or invoices, your upcoming deadlines will appear here.
          </p>
        </div>
      </CardContent>
    </Card>
  );
}

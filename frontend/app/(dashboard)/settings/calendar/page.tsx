"use client";

import { useEffect, useMemo, useState } from "react";
import { toast } from "sonner";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { useCalendarStore } from "@/lib/stores/calendar";

export default function CalendarSettingsPage() {
  const {
    connections,
    isLoading,
    fetchConnections,
    createConnection,
    updateConnection,
    deleteConnection,
  } = useCalendarStore();

  const [provider, setProvider] = useState<"google" | "caldav">("google");
  const [name, setName] = useState("");
  const [calendarId, setCalendarId] = useState("primary");
  const [syncEnabled, setSyncEnabled] = useState(true);
  const [autoProjectDeadlines, setAutoProjectDeadlines] = useState(true);
  const [autoTaskDues, setAutoTaskDues] = useState(true);
  const [autoInvoiceReminders, setAutoInvoiceReminders] = useState(true);

  useEffect(() => {
    fetchConnections();
  }, [fetchConnections]);

  const credentials = useMemo(() => {
    if (provider === "google") {
      return {
        access_token: "ui-token",
        refresh_token: "ui-refresh",
      };
    }

    return {
      base_url: "https://caldav.example.test",
      username: "demo",
      password: "demo",
    };
  }, [provider]);

  const handleCreateConnection = async () => {
    if (!name.trim()) {
      toast.error("Connection name is required");
      return;
    }

    try {
      await createConnection({
        provider,
        name: name.trim(),
        credentials,
        calendar_id: calendarId.trim() || undefined,
        sync_enabled: syncEnabled,
      });
      toast.success("Calendar connection created");
      setName("");
      await fetchConnections();
    } catch (error) {
      toast.error((error as Error).message || "Unable to create connection");
    }
  };

  return (
    <div className="space-y-6">
      <div className="space-y-2">
        <h1 className="text-3xl font-bold">Calendar settings</h1>
        <p className="text-sm text-muted-foreground">
          Manage external calendar connections and auto-event rules.
        </p>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Add connection</CardTitle>
        </CardHeader>
        <CardContent className="space-y-4">
          <div className="grid gap-3 md:grid-cols-2">
            <div className="space-y-2">
              <Label htmlFor="calendar-connection-name">Connection name</Label>
              <Input
                id="calendar-connection-name"
                aria-label="Connection name"
                value={name}
                onChange={(event) => setName(event.target.value)}
                placeholder="Google Work"
              />
            </div>

            <div className="space-y-2">
              <Label htmlFor="calendar-connection-provider">Provider</Label>
              <select
                id="calendar-connection-provider"
                className="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                value={provider}
                onChange={(event) =>
                  setProvider(event.target.value as "google" | "caldav")
                }
              >
                <option value="google">Google Calendar</option>
                <option value="caldav">CalDAV</option>
              </select>
            </div>

            <div className="space-y-2">
              <Label htmlFor="calendar-connection-id">Calendar ID</Label>
              <Input
                id="calendar-connection-id"
                value={calendarId}
                onChange={(event) => setCalendarId(event.target.value)}
              />
            </div>

            <div className="flex items-center gap-2 pt-8">
              <input
                id="calendar-sync-enabled"
                type="checkbox"
                checked={syncEnabled}
                onChange={(event) => setSyncEnabled(event.target.checked)}
              />
              <Label htmlFor="calendar-sync-enabled">Enable sync</Label>
            </div>
          </div>

          <div className="flex justify-end">
            <Button onClick={handleCreateConnection} disabled={isLoading}>
              {isLoading ? "Saving..." : "Save connection"}
            </Button>
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>Auto-event rules</CardTitle>
        </CardHeader>
        <CardContent className="space-y-2 text-sm">
          <label className="flex items-center gap-2">
            <input
              type="checkbox"
              checked={autoProjectDeadlines}
              onChange={(event) => setAutoProjectDeadlines(event.target.checked)}
            />
            Project deadlines
          </label>
          <label className="flex items-center gap-2">
            <input
              type="checkbox"
              checked={autoTaskDues}
              onChange={(event) => setAutoTaskDues(event.target.checked)}
            />
            Task due dates
          </label>
          <label className="flex items-center gap-2">
            <input
              type="checkbox"
              checked={autoInvoiceReminders}
              onChange={(event) => setAutoInvoiceReminders(event.target.checked)}
            />
            Invoice reminders
          </label>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>Existing connections</CardTitle>
        </CardHeader>
        <CardContent className="space-y-3">
          {connections.length === 0 ? (
            <p className="text-sm text-muted-foreground">
              No connection configured yet.
            </p>
          ) : (
            connections.map((connection) => (
              <div
                key={connection.id}
                className="rounded-md border bg-muted/20 p-3"
              >
                <div className="flex flex-wrap items-center justify-between gap-2">
                  <div>
                    <p className="text-sm font-semibold">{connection.name}</p>
                    <p className="text-xs text-muted-foreground">
                      {connection.provider} -{" "}
                      {connection.sync_enabled ? "enabled" : "disabled"}
                    </p>
                  </div>
                  <div className="flex gap-2">
                    <Button
                      size="sm"
                      variant="outline"
                      onClick={async () => {
                        await updateConnection(connection.id, {
                          sync_enabled: !connection.sync_enabled,
                        });
                        await fetchConnections();
                      }}
                    >
                      {connection.sync_enabled ? "Disable" : "Enable"}
                    </Button>
                    <Button
                      size="sm"
                      variant="destructive"
                      onClick={async () => {
                        await deleteConnection(connection.id);
                        await fetchConnections();
                      }}
                    >
                      Delete
                    </Button>
                  </div>
                </div>
              </div>
            ))
          )}
        </CardContent>
      </Card>
    </div>
  );
}

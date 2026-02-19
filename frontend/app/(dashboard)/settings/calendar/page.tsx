"use client";

import { useEffect, useMemo, useState } from "react";
import { toast } from "sonner";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { useCalendarStore } from "@/lib/stores/calendar";
import { useI18n } from "@/components/providers/i18n-provider";

export default function CalendarSettingsPage() {
  const { t } = useI18n();
  const {
    connections,
    isLoading,
    fetchConnections,
    createConnection,
    updateConnection,
    deleteConnection,
    autoEventRules,
    fetchAutoEventRules,
    updateAutoEventRules,
  } = useCalendarStore();

  const [provider, setProvider] = useState<"google" | "caldav">("google");
  const [name, setName] = useState("");
  const [calendarId, setCalendarId] = useState("primary");
  const [syncEnabled, setSyncEnabled] = useState(true);
  const [autoProjectDeadlines, setAutoProjectDeadlines] = useState(true);
  const [autoTaskDues, setAutoTaskDues] = useState(true);
  const [autoInvoiceReminders, setAutoInvoiceReminders] = useState(true);
  const [isSavingRules, setIsSavingRules] = useState(false);

  useEffect(() => {
    fetchConnections();
    fetchAutoEventRules().catch(() => {
      toast.error(t("settings.calendarSettings.toasts.loadFailed"));
    });
  }, [fetchConnections, fetchAutoEventRules]);

  useEffect(() => {
    setAutoProjectDeadlines(autoEventRules.project_deadlines);
    setAutoTaskDues(autoEventRules.task_due_dates);
    setAutoInvoiceReminders(autoEventRules.invoice_reminders);
  }, [autoEventRules]);

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
      toast.error(t("settings.calendarSettings.toasts.connectionNameRequired"));
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
      toast.success(t("settings.calendarSettings.toasts.connectionCreated"));
      setName("");
      await fetchConnections();
    } catch (error) {
      toast.error(
        (error as Error).message ||
          t("settings.calendarSettings.toasts.connectionFailed")
      );
    }
  };

  const handleSaveAutoEventRules = async () => {
    setIsSavingRules(true);
    try {
      await updateAutoEventRules({
        project_deadlines: autoProjectDeadlines,
        task_due_dates: autoTaskDues,
        invoice_reminders: autoInvoiceReminders,
      });
      toast.success(t("settings.calendarSettings.toasts.autoRulesSaved"));
    } catch (error) {
      toast.error(
        (error as Error).message ||
          t("settings.calendarSettings.toasts.autoRulesFailed")
      );
    } finally {
      setIsSavingRules(false);
    }
  };

  return (
    <div className="space-y-6">
      <div className="space-y-2">
        <h1 className="text-3xl font-bold">
          {t("settings.calendarSettings.title")}
        </h1>
        <p className="text-sm text-muted-foreground">
          {t("settings.calendarSettings.description")}
        </p>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>{t("settings.calendarSettings.addConnection")}</CardTitle>
        </CardHeader>
        <CardContent className="space-y-4">
          <div className="grid gap-3 md:grid-cols-2">
            <div className="space-y-2">
              <Label htmlFor="calendar-connection-name">
                {t("settings.calendarSettings.connectionName")}
              </Label>
              <Input
                id="calendar-connection-name"
                aria-label="Connection name"
                value={name}
                onChange={(event) => setName(event.target.value)}
                placeholder="Google Work"
              />
            </div>

            <div className="space-y-2">
              <Label htmlFor="calendar-connection-provider">
                {t("settings.calendarSettings.provider")}
              </Label>
              <select
                id="calendar-connection-provider"
                className="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                value={provider}
                onChange={(event) =>
                  setProvider(event.target.value as "google" | "caldav")
                }
              >
                <option value="google">
                  {t("settings.calendarSettings.googleCalendar")}
                </option>
                <option value="caldav">
                  {t("settings.calendarSettings.caldav")}
                </option>
              </select>
            </div>

            <div className="space-y-2">
              <Label htmlFor="calendar-connection-id">
                {t("settings.calendarSettings.calendarId")}
              </Label>
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
              <Label htmlFor="calendar-sync-enabled">
                {t("settings.calendarSettings.enableSync")}
              </Label>
            </div>
          </div>

          <div className="flex justify-end">
            <Button onClick={handleCreateConnection} disabled={isLoading}>
              {isLoading
                ? t("settings.calendarSettings.saving")
                : t("settings.calendarSettings.saveConnection")}
            </Button>
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>{t("settings.calendarSettings.autoEventRules")}</CardTitle>
        </CardHeader>
        <CardContent className="space-y-2 text-sm">
          <label className="flex items-center gap-2">
            <input
              type="checkbox"
              checked={autoProjectDeadlines}
              onChange={(event) =>
                setAutoProjectDeadlines(event.target.checked)
              }
            />
            {t("settings.calendarSettings.projectDeadlines")}
          </label>
          <label className="flex items-center gap-2">
            <input
              type="checkbox"
              checked={autoTaskDues}
              onChange={(event) => setAutoTaskDues(event.target.checked)}
            />
            {t("settings.calendarSettings.taskDueDates")}
          </label>
          <label className="flex items-center gap-2">
            <input
              type="checkbox"
              checked={autoInvoiceReminders}
              onChange={(event) =>
                setAutoInvoiceReminders(event.target.checked)
              }
            />
            {t("settings.calendarSettings.invoiceReminders")}
          </label>
          <div className="flex justify-end pt-2">
            <Button
              type="button"
              onClick={handleSaveAutoEventRules}
              disabled={isSavingRules}
            >
              {isSavingRules
                ? t("settings.calendarSettings.saving")
                : t("settings.calendarSettings.saveAutoRules")}
            </Button>
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>
            {t("settings.calendarSettings.existingConnections")}
          </CardTitle>
        </CardHeader>
        <CardContent className="space-y-3">
          {connections.length === 0 ? (
            <p className="text-sm text-muted-foreground">
              {t("settings.calendarSettings.noConnections")}
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
                      {connection.sync_enabled
                        ? t("settings.calendarSettings.enable")
                        : t("settings.calendarSettings.disable")}
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
                      {connection.sync_enabled
                        ? t("settings.calendarSettings.disable")
                        : t("settings.calendarSettings.enable")}
                    </Button>
                    <Button
                      size="sm"
                      variant="destructive"
                      onClick={async () => {
                        await deleteConnection(connection.id);
                        await fetchConnections();
                      }}
                    >
                      {t("settings.calendarSettings.delete")}
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

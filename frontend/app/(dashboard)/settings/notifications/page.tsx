"use client";

import { useState } from "react";
import { toast } from "sonner";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { useCampaignStore } from "@/lib/stores/campaigns";
import { useI18n } from "@/components/providers/i18n-provider";

interface NotificationRow {
  key: "invoice_paid" | "campaign_completed" | "task_overdue";
  label: string;
  email: boolean;
  in_app: boolean;
}

export default function NotificationSettingsPage() {
  const { t } = useI18n();
  const { updateNotificationPreferences, isLoading } = useCampaignStore();
  const [rows, setRows] = useState<NotificationRow[]>([
    {
      key: "invoice_paid",
      label: t("settings.notifications.invoicePaid"),
      email: true,
      in_app: true,
    },
    {
      key: "campaign_completed",
      label: t("settings.notifications.campaignCompleted"),
      email: true,
      in_app: true,
    },
    {
      key: "task_overdue",
      label: t("settings.notifications.taskOverdue"),
      email: false,
      in_app: true,
    },
  ]);

  const updateRow = (
    key: NotificationRow["key"],
    channel: "email" | "in_app",
    checked: boolean
  ) => {
    setRows((current) =>
      current.map((row) => {
        if (row.key !== key) {
          return row;
        }

        return {
          ...row,
          [channel]: checked,
        };
      })
    );
  };

  const save = async () => {
    const payload = rows.reduce<
      Record<string, { email: boolean; in_app: boolean }>
    >((accumulator, row) => {
      accumulator[row.key] = {
        email: row.email,
        in_app: row.in_app,
      };
      return accumulator;
    }, {});

    try {
      await updateNotificationPreferences(payload);
      toast.success(t("settings.notifications.toasts.success"));
    } catch (error) {
      toast.error(
        (error as Error).message || t("settings.notifications.toasts.failed")
      );
    }
  };

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-3xl font-bold">
          {t("settings.notifications.title")}
        </h1>
        <p className="text-sm text-muted-foreground">
          {t("settings.notifications.description")}
        </p>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>{t("settings.notifications.channels")}</CardTitle>
        </CardHeader>
        <CardContent className="space-y-3">
          {rows.map((row) => (
            <div
              key={row.key}
              className="flex items-center justify-between rounded-md border p-3"
            >
              <p className="text-sm font-medium">{row.label}</p>
              <div className="flex items-center gap-4 text-sm">
                <label className="flex items-center gap-2">
                  <input
                    type="checkbox"
                    checked={row.email}
                    onChange={(event) =>
                      updateRow(row.key, "email", event.target.checked)
                    }
                  />
                  {t("settings.notifications.emailChannel")}
                </label>
                <label className="flex items-center gap-2">
                  <input
                    type="checkbox"
                    checked={row.in_app}
                    onChange={(event) =>
                      updateRow(row.key, "in_app", event.target.checked)
                    }
                  />
                  {t("settings.notifications.inApp")}
                </label>
              </div>
            </div>
          ))}

          <div className="flex justify-end">
            <Button onClick={save} disabled={isLoading}>
              {isLoading
                ? t("settings.notifications.saving")
                : t("settings.notifications.saveChanges")}
            </Button>
          </div>
        </CardContent>
      </Card>
    </div>
  );
}

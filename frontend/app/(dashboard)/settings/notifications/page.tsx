"use client";

import { useState } from "react";
import { toast } from "sonner";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { useCampaignStore } from "@/lib/stores/campaigns";

interface NotificationRow {
  key: "invoice_paid" | "campaign_completed" | "task_overdue";
  label: string;
  email: boolean;
  in_app: boolean;
}

export default function NotificationSettingsPage() {
  const { updateNotificationPreferences, isLoading } = useCampaignStore();
  const [rows, setRows] = useState<NotificationRow[]>([
    {
      key: "invoice_paid",
      label: "Invoice paid",
      email: true,
      in_app: true,
    },
    {
      key: "campaign_completed",
      label: "Campaign completed",
      email: true,
      in_app: true,
    },
    {
      key: "task_overdue",
      label: "Task overdue",
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
      toast.success("Notification preferences updated");
    } catch (error) {
      toast.error(
        (error as Error).message || "Unable to update notification preferences"
      );
    }
  };

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-3xl font-bold">Notification preferences</h1>
        <p className="text-sm text-muted-foreground">
          Choose where each alert should be delivered.
        </p>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Channels</CardTitle>
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
                  Email
                </label>
                <label className="flex items-center gap-2">
                  <input
                    type="checkbox"
                    checked={row.in_app}
                    onChange={(event) =>
                      updateRow(row.key, "in_app", event.target.checked)
                    }
                  />
                  In app
                </label>
              </div>
            </div>
          ))}

          <div className="flex justify-end">
            <Button onClick={save} disabled={isLoading}>
              {isLoading ? "Saving..." : "Save changes"}
            </Button>
          </div>
        </CardContent>
      </Card>
    </div>
  );
}

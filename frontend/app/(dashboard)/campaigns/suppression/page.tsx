"use client";

import { useEffect, useState } from "react";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { SuppressionListTable } from "@/components/campaigns/suppression-list-table";
import { useSuppressionListStore } from "@/lib/stores/suppression-list";

export default function SuppressionListPage() {
  const { entries, total, fetchEntries, addEntry, removeEntry, exportCsv } =
    useSuppressionListStore();
  const [email, setEmail] = useState("");

  useEffect(() => {
    fetchEntries().catch(() => undefined);
  }, [fetchEntries]);

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-3xl font-bold">Suppression List</h1>
        <p className="text-sm text-muted-foreground">
          Centralized blocklist for unsubscribes and hard bounces.
        </p>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Add email</CardTitle>
        </CardHeader>
        <CardContent className="flex flex-wrap items-end gap-3">
          <div className="min-w-[260px] flex-1 space-y-2">
            <Label htmlFor="suppression-email">Email</Label>
            <Input
              id="suppression-email"
              value={email}
              onChange={(event) => setEmail(event.target.value)}
            />
          </div>
          <Button
            type="button"
            onClick={async () => {
              await addEntry(email);
              setEmail("");
            }}
          >
            Add
          </Button>
          <Button
            type="button"
            variant="outline"
            onClick={() => void exportCsv()}
          >
            Export CSV
          </Button>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>Entries ({total})</CardTitle>
        </CardHeader>
        <CardContent>
          <SuppressionListTable
            entries={entries}
            onRemove={(id) => void removeEntry(id)}
          />
        </CardContent>
      </Card>
    </div>
  );
}

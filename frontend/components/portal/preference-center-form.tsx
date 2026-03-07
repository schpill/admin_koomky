"use client";

import { useState } from "react";
import { Button } from "@/components/ui/button";
import { Checkbox } from "@/components/ui/checkbox";
import { Label } from "@/components/ui/label";

export interface PreferenceItem {
  category: "newsletter" | "promotional" | "transactional";
  subscribed: boolean;
}

interface PreferenceCenterFormProps {
  initialPreferences: PreferenceItem[];
  onSubmit: (preferences: PreferenceItem[]) => Promise<void> | void;
}

export function PreferenceCenterForm({
  initialPreferences,
  onSubmit,
}: PreferenceCenterFormProps) {
  const [preferences, setPreferences] =
    useState<PreferenceItem[]>(initialPreferences);
  const [isSaving, setIsSaving] = useState(false);

  const togglePreference = (category: PreferenceItem["category"]) => {
    setPreferences((current) =>
      current.map((preference) =>
        preference.category === category
          ? { ...preference, subscribed: !preference.subscribed }
          : preference
      )
    );
  };

  const unsubscribeAllMarketing = () => {
    setPreferences((current) =>
      current.map((preference) =>
        preference.category === "transactional"
          ? preference
          : { ...preference, subscribed: false }
      )
    );
  };

  const submit = async () => {
    setIsSaving(true);
    try {
      await onSubmit(preferences);
    } finally {
      setIsSaving(false);
    }
  };

  return (
    <div className="space-y-6 rounded-2xl border border-border bg-card p-6">
      <div>
        <h1 className="text-2xl font-semibold">Communication preferences</h1>
        <p className="text-sm text-muted-foreground">
          Choose which categories of email you want to receive.
        </p>
      </div>

      <div className="space-y-4">
        {preferences.map((preference) => (
          <div
            key={preference.category}
            className="flex items-center justify-between rounded-xl border border-border px-4 py-3"
          >
            <div>
              <Label htmlFor={preference.category} className="capitalize">
                {preference.category}
              </Label>
              <p className="text-xs text-muted-foreground">
                {preference.category === "transactional"
                  ? "Operational and account-related emails."
                  : "Marketing and lifecycle messages."}
              </p>
            </div>
            <Checkbox
              id={preference.category}
              checked={preference.subscribed}
              onCheckedChange={() => togglePreference(preference.category)}
            />
          </div>
        ))}
      </div>

      <div className="flex gap-3">
        <Button onClick={submit} disabled={isSaving}>
          Save preferences
        </Button>
        <Button
          variant="outline"
          onClick={unsubscribeAllMarketing}
          disabled={isSaving}
        >
          Unsubscribe from all marketing
        </Button>
      </div>
    </div>
  );
}

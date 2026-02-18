"use client";

import { FormEvent, useEffect, useState } from "react";
import { toast } from "sonner";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { apiClient } from "@/lib/api";

interface PortalSettings {
  portal_enabled: boolean;
  custom_logo?: string | null;
  custom_color?: string | null;
  welcome_message?: string | null;
  payment_enabled: boolean;
  quote_acceptance_enabled: boolean;
  stripe_publishable_key?: string | null;
  stripe_secret_key?: string | null;
  stripe_webhook_secret?: string | null;
  payment_methods_enabled?: string[] | null;
}

const defaultSettings: PortalSettings = {
  portal_enabled: false,
  custom_logo: "",
  custom_color: "#2459ff",
  welcome_message: "",
  payment_enabled: false,
  quote_acceptance_enabled: true,
  stripe_publishable_key: "",
  stripe_secret_key: "",
  stripe_webhook_secret: "",
  payment_methods_enabled: ["card"],
};

export default function PortalSettingsPage() {
  const [settings, setSettings] = useState<PortalSettings>(defaultSettings);
  const [isLoading, setLoading] = useState(true);
  const [isSaving, setSaving] = useState(false);

  useEffect(() => {
    apiClient
      .get<PortalSettings>("/settings/portal")
      .then((response) => {
        setSettings({
          ...defaultSettings,
          ...response.data,
          custom_logo: response.data.custom_logo || "",
          custom_color: response.data.custom_color || "#2459ff",
          welcome_message: response.data.welcome_message || "",
          stripe_publishable_key: response.data.stripe_publishable_key || "",
          stripe_secret_key: response.data.stripe_secret_key || "",
          stripe_webhook_secret: response.data.stripe_webhook_secret || "",
          payment_methods_enabled:
            response.data.payment_methods_enabled || ["card"],
        });
      })
      .catch((error) => {
        toast.error((error as Error).message || "Unable to load portal settings");
      })
      .finally(() => setLoading(false));
  }, []);

  const submit = async (event: FormEvent) => {
    event.preventDefault();

    setSaving(true);
    try {
      const response = await apiClient.put<PortalSettings>("/settings/portal", {
        ...settings,
        payment_methods_enabled: settings.payment_methods_enabled || ["card"],
      });
      setSettings({ ...settings, ...response.data });
      toast.success("Portal settings updated");
    } catch (error) {
      toast.error((error as Error).message || "Unable to update portal settings");
    } finally {
      setSaving(false);
    }
  };

  if (isLoading) {
    return <p className="text-sm text-muted-foreground">Loading settings...</p>;
  }

  return (
    <form className="space-y-6" onSubmit={submit}>
      <div className="space-y-1">
        <h1 className="text-3xl font-bold">Portal settings</h1>
        <p className="text-sm text-muted-foreground">
          Configure branding, portal features and Stripe payment credentials.
        </p>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Portal configuration</CardTitle>
        </CardHeader>
        <CardContent className="space-y-4">
          <label className="flex items-center gap-2 text-sm">
            <input
              type="checkbox"
              checked={settings.portal_enabled}
              onChange={(event) =>
                setSettings((current) => ({
                  ...current,
                  portal_enabled: event.target.checked,
                }))
              }
            />
            Enable client portal
          </label>

          <label className="flex items-center gap-2 text-sm">
            <input
              type="checkbox"
              checked={settings.quote_acceptance_enabled}
              onChange={(event) =>
                setSettings((current) => ({
                  ...current,
                  quote_acceptance_enabled: event.target.checked,
                }))
              }
            />
            Allow quote acceptance from portal
          </label>

          <label className="flex items-center gap-2 text-sm">
            <input
              type="checkbox"
              checked={settings.payment_enabled}
              onChange={(event) =>
                setSettings((current) => ({
                  ...current,
                  payment_enabled: event.target.checked,
                }))
              }
            />
            Enable online payments in portal
          </label>

          <div className="grid gap-4 md:grid-cols-2">
            <div className="space-y-2">
              <Label htmlFor="portal-custom-logo">Custom logo URL</Label>
              <Input
                id="portal-custom-logo"
                value={settings.custom_logo || ""}
                onChange={(event) =>
                  setSettings((current) => ({
                    ...current,
                    custom_logo: event.target.value,
                  }))
                }
                placeholder="https://cdn.example.com/logo.png"
              />
            </div>
            <div className="space-y-2">
              <Label htmlFor="portal-custom-color">Primary color</Label>
              <Input
                id="portal-custom-color"
                type="color"
                value={settings.custom_color || "#2459ff"}
                onChange={(event) =>
                  setSettings((current) => ({
                    ...current,
                    custom_color: event.target.value,
                  }))
                }
              />
            </div>
          </div>

          <div className="space-y-2">
            <Label htmlFor="portal-welcome-message">Welcome message</Label>
            <Textarea
              id="portal-welcome-message"
              rows={3}
              value={settings.welcome_message || ""}
              onChange={(event) =>
                setSettings((current) => ({
                  ...current,
                  welcome_message: event.target.value,
                }))
              }
            />
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>Stripe configuration</CardTitle>
        </CardHeader>
        <CardContent className="space-y-4">
          <div className="space-y-2">
            <Label htmlFor="stripe-publishable-key">Publishable key</Label>
            <Input
              id="stripe-publishable-key"
              value={settings.stripe_publishable_key || ""}
              onChange={(event) =>
                setSettings((current) => ({
                  ...current,
                  stripe_publishable_key: event.target.value,
                }))
              }
            />
          </div>
          <div className="space-y-2">
            <Label htmlFor="stripe-secret-key">Secret key</Label>
            <Input
              id="stripe-secret-key"
              value={settings.stripe_secret_key || ""}
              onChange={(event) =>
                setSettings((current) => ({
                  ...current,
                  stripe_secret_key: event.target.value,
                }))
              }
            />
          </div>
          <div className="space-y-2">
            <Label htmlFor="stripe-webhook-secret">Webhook secret</Label>
            <Input
              id="stripe-webhook-secret"
              value={settings.stripe_webhook_secret || ""}
              onChange={(event) =>
                setSettings((current) => ({
                  ...current,
                  stripe_webhook_secret: event.target.value,
                }))
              }
            />
          </div>
        </CardContent>
      </Card>

      <div className="flex justify-end">
        <Button type="submit" disabled={isSaving}>
          {isSaving ? "Saving..." : "Save portal settings"}
        </Button>
      </div>
    </form>
  );
}

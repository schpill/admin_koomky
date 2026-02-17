"use client";

import { useState } from "react";
import { toast } from "sonner";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { useCampaignStore } from "@/lib/stores/campaigns";

export default function SmsSettingsPage() {
  const { updateSmsSettings, isLoading } = useCampaignStore();

  const [provider, setProvider] = useState("twilio");
  const [from, setFrom] = useState("");
  const [accountSid, setAccountSid] = useState("");
  const [authToken, setAuthToken] = useState("");
  const [apiKey, setApiKey] = useState("");
  const [apiSecret, setApiSecret] = useState("");

  const save = async () => {
    try {
      await updateSmsSettings({
        provider,
        from: from || null,
        account_sid: accountSid || null,
        auth_token: authToken || null,
        api_key: apiKey || null,
        api_secret: apiSecret || null,
      });
      toast.success("SMS settings updated");
    } catch (error) {
      toast.error((error as Error).message || "Unable to update SMS settings");
    }
  };

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-3xl font-bold">SMS settings</h1>
        <p className="text-sm text-muted-foreground">
          Configure provider credentials for SMS campaigns.
        </p>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Provider configuration</CardTitle>
        </CardHeader>
        <CardContent className="space-y-4">
          <div className="space-y-2">
            <Label htmlFor="sms-provider">Provider</Label>
            <select
              id="sms-provider"
              value={provider}
              onChange={(event) => setProvider(event.target.value)}
              className="h-9 w-full rounded-md border border-input bg-background px-3 text-sm"
            >
              <option value="twilio">Twilio</option>
              <option value="vonage">Vonage</option>
            </select>
          </div>

          <div className="grid gap-4 md:grid-cols-2">
            <div className="space-y-2">
              <Label htmlFor="sms-from">Sender</Label>
              <Input
                id="sms-from"
                value={from}
                onChange={(event) => setFrom(event.target.value)}
                placeholder="+33612345678"
              />
            </div>
            <div className="space-y-2">
              <Label htmlFor="account-sid">Account SID</Label>
              <Input
                id="account-sid"
                value={accountSid}
                onChange={(event) => setAccountSid(event.target.value)}
              />
            </div>
            <div className="space-y-2">
              <Label htmlFor="auth-token">Auth token</Label>
              <Input
                id="auth-token"
                type="password"
                value={authToken}
                onChange={(event) => setAuthToken(event.target.value)}
              />
            </div>
            <div className="space-y-2">
              <Label htmlFor="sms-api-key">API key</Label>
              <Input
                id="sms-api-key"
                value={apiKey}
                onChange={(event) => setApiKey(event.target.value)}
              />
            </div>
          </div>

          <div className="space-y-2">
            <Label htmlFor="sms-api-secret">API secret</Label>
            <Input
              id="sms-api-secret"
              type="password"
              value={apiSecret}
              onChange={(event) => setApiSecret(event.target.value)}
            />
          </div>

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

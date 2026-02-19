"use client";

import { useState } from "react";
import { toast } from "sonner";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { useCampaignStore } from "@/lib/stores/campaigns";
import { useI18n } from "@/components/providers/i18n-provider";

export default function SmsSettingsPage() {
  const { t } = useI18n();
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
      toast.success(t("settings.sms.toasts.success"));
    } catch (error) {
      toast.error((error as Error).message || t("settings.sms.toasts.failed"));
    }
  };

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-3xl font-bold">{t("settings.sms.title")}</h1>
        <p className="text-sm text-muted-foreground">
          {t("settings.sms.description")}
        </p>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>{t("settings.sms.providerConfig")}</CardTitle>
        </CardHeader>
        <CardContent className="space-y-4">
          <div className="space-y-2">
            <Label htmlFor="sms-provider">{t("settings.sms.provider")}</Label>
            <select
              id="sms-provider"
              value={provider}
              onChange={(event) => setProvider(event.target.value)}
              className="h-9 w-full rounded-md border border-input bg-background px-3 text-sm"
            >
              <option value="twilio">{t("settings.sms.twilio")}</option>
              <option value="vonage">{t("settings.sms.vonage")}</option>
            </select>
          </div>

          <div className="grid gap-4 md:grid-cols-2">
            <div className="space-y-2">
              <Label htmlFor="sms-from">{t("settings.sms.sender")}</Label>
              <Input
                id="sms-from"
                value={from}
                onChange={(event) => setFrom(event.target.value)}
                placeholder="+33612345678"
              />
            </div>
            <div className="space-y-2">
              <Label htmlFor="account-sid">
                {t("settings.sms.accountSid")}
              </Label>
              <Input
                id="account-sid"
                value={accountSid}
                onChange={(event) => setAccountSid(event.target.value)}
              />
            </div>
            <div className="space-y-2">
              <Label htmlFor="auth-token">{t("settings.sms.authToken")}</Label>
              <Input
                id="auth-token"
                type="password"
                value={authToken}
                onChange={(event) => setAuthToken(event.target.value)}
              />
            </div>
            <div className="space-y-2">
              <Label htmlFor="sms-api-key">{t("settings.sms.apiKey")}</Label>
              <Input
                id="sms-api-key"
                value={apiKey}
                onChange={(event) => setApiKey(event.target.value)}
              />
            </div>
          </div>

          <div className="space-y-2">
            <Label htmlFor="sms-api-secret">
              {t("settings.sms.apiSecret")}
            </Label>
            <Input
              id="sms-api-secret"
              type="password"
              value={apiSecret}
              onChange={(event) => setApiSecret(event.target.value)}
            />
          </div>

          <div className="flex justify-end">
            <Button onClick={save} disabled={isLoading}>
              {isLoading
                ? t("settings.sms.saving")
                : t("settings.sms.saveChanges")}
            </Button>
          </div>
        </CardContent>
      </Card>
    </div>
  );
}

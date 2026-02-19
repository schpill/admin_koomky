"use client";

import { useState } from "react";
import { toast } from "sonner";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { useCampaignStore } from "@/lib/stores/campaigns";
import { useI18n } from "@/components/providers/i18n-provider";

export default function EmailSettingsPage() {
  const { t } = useI18n();
  const { updateEmailSettings, isLoading } = useCampaignStore();

  const [provider, setProvider] = useState("smtp");
  const [fromEmail, setFromEmail] = useState("");
  const [fromName, setFromName] = useState("");
  const [replyTo, setReplyTo] = useState("");
  const [smtpHost, setSmtpHost] = useState("");
  const [smtpPort, setSmtpPort] = useState("587");
  const [smtpUsername, setSmtpUsername] = useState("");
  const [smtpPassword, setSmtpPassword] = useState("");
  const [apiKey, setApiKey] = useState("");
  const [apiSecret, setApiSecret] = useState("");
  const [apiRegion, setApiRegion] = useState("us-east-1");

  const isApiProvider =
    provider === "mailgun" || provider === "ses" || provider === "postmark";

  const save = async () => {
    try {
      await updateEmailSettings({
        provider,
        from_email: fromEmail || null,
        from_name: fromName || null,
        reply_to: replyTo || null,
        smtp_host: provider === "smtp" ? smtpHost || null : null,
        smtp_port: provider === "smtp" ? Number(smtpPort) || null : null,
        smtp_username: provider === "smtp" ? smtpUsername || null : null,
        smtp_password: provider === "smtp" ? smtpPassword || null : null,
        api_key: isApiProvider ? apiKey || null : null,
        api_secret: provider === "ses" ? apiSecret || null : null,
        api_region: provider === "ses" ? apiRegion || null : null,
      });
      toast.success(t("settings.email.toasts.success"));
    } catch (error) {
      toast.error(
        (error as Error).message || t("settings.email.toasts.failed")
      );
    }
  };

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-3xl font-bold">{t("settings.email.title")}</h1>
        <p className="text-sm text-muted-foreground">
          {t("settings.email.description")}
        </p>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>{t("settings.email.deliveryConfig")}</CardTitle>
        </CardHeader>
        <CardContent className="space-y-4">
          <div className="space-y-2">
            <Label htmlFor="email-provider">
              {t("settings.email.provider")}
            </Label>
            <select
              id="email-provider"
              value={provider}
              onChange={(event) => setProvider(event.target.value)}
              className="h-9 w-full rounded-md border border-input bg-background px-3 text-sm"
            >
              <option value="smtp">{t("settings.email.smtp")}</option>
              <option value="mailgun">{t("settings.email.mailgun")}</option>
              <option value="ses">{t("settings.email.ses")}</option>
              <option value="postmark">{t("settings.email.postmark")}</option>
              <option value="sendmail">{t("settings.email.sendmail")}</option>
            </select>
          </div>

          <div className="grid gap-4 md:grid-cols-2">
            <div className="space-y-2">
              <Label htmlFor="from-email">
                {t("settings.email.fromEmail")}
              </Label>
              <Input
                id="from-email"
                value={fromEmail}
                onChange={(event) => setFromEmail(event.target.value)}
              />
            </div>
            <div className="space-y-2">
              <Label htmlFor="from-name">{t("settings.email.fromName")}</Label>
              <Input
                id="from-name"
                value={fromName}
                onChange={(event) => setFromName(event.target.value)}
              />
            </div>
          </div>

          <div className="space-y-2">
            <Label htmlFor="reply-to">{t("settings.email.replyTo")}</Label>
            <Input
              id="reply-to"
              value={replyTo}
              onChange={(event) => setReplyTo(event.target.value)}
            />
          </div>

          {provider === "smtp" && (
            <div className="grid gap-4 md:grid-cols-2">
              <div className="space-y-2">
                <Label htmlFor="smtp-host">
                  {t("settings.email.smtpHost")}
                </Label>
                <Input
                  id="smtp-host"
                  value={smtpHost}
                  onChange={(event) => setSmtpHost(event.target.value)}
                />
              </div>
              <div className="space-y-2">
                <Label htmlFor="smtp-port">
                  {t("settings.email.smtpPort")}
                </Label>
                <Input
                  id="smtp-port"
                  value={smtpPort}
                  onChange={(event) => setSmtpPort(event.target.value)}
                />
              </div>
              <div className="space-y-2">
                <Label htmlFor="smtp-user">
                  {t("settings.email.smtpUsername")}
                </Label>
                <Input
                  id="smtp-user"
                  value={smtpUsername}
                  onChange={(event) => setSmtpUsername(event.target.value)}
                />
              </div>
              <div className="space-y-2">
                <Label htmlFor="smtp-password">
                  {t("settings.email.smtpPassword")}
                </Label>
                <Input
                  id="smtp-password"
                  type="password"
                  value={smtpPassword}
                  onChange={(event) => setSmtpPassword(event.target.value)}
                />
              </div>
            </div>
          )}

          {isApiProvider && (
            <div className="grid gap-4 md:grid-cols-2">
              <div className="space-y-2">
                <Label htmlFor="api-key">{t("settings.email.apiKey")}</Label>
                <Input
                  id="api-key"
                  value={apiKey}
                  onChange={(event) => setApiKey(event.target.value)}
                />
              </div>

              {provider === "ses" && (
                <div className="space-y-2">
                  <Label htmlFor="api-secret">
                    {t("settings.email.apiSecret")}
                  </Label>
                  <Input
                    id="api-secret"
                    type="password"
                    value={apiSecret}
                    onChange={(event) => setApiSecret(event.target.value)}
                  />
                </div>
              )}

              {provider === "ses" && (
                <div className="space-y-2">
                  <Label htmlFor="api-region">
                    {t("settings.email.awsRegion")}
                  </Label>
                  <Input
                    id="api-region"
                    value={apiRegion}
                    onChange={(event) => setApiRegion(event.target.value)}
                  />
                </div>
              )}
            </div>
          )}

          <div className="flex justify-end">
            <Button onClick={save} disabled={isLoading}>
              {isLoading
                ? t("settings.email.saving")
                : t("settings.email.saveChanges")}
            </Button>
          </div>
        </CardContent>
      </Card>
    </div>
  );
}

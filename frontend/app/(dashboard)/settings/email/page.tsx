"use client";

import { useState } from "react";
import { toast } from "sonner";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { useCampaignStore } from "@/lib/stores/campaigns";

export default function EmailSettingsPage() {
  const { updateEmailSettings, isLoading } = useCampaignStore();

  const [provider, setProvider] = useState("smtp");
  const [fromEmail, setFromEmail] = useState("");
  const [fromName, setFromName] = useState("");
  const [replyTo, setReplyTo] = useState("");
  const [smtpHost, setSmtpHost] = useState("");
  const [smtpPort, setSmtpPort] = useState("587");
  const [smtpUsername, setSmtpUsername] = useState("");
  const [smtpPassword, setSmtpPassword] = useState("");

  const save = async () => {
    try {
      await updateEmailSettings({
        provider,
        from_email: fromEmail || null,
        from_name: fromName || null,
        reply_to: replyTo || null,
        smtp_host: smtpHost || null,
        smtp_port: Number(smtpPort) || null,
        smtp_username: smtpUsername || null,
        smtp_password: smtpPassword || null,
      });
      toast.success("Email settings updated");
    } catch (error) {
      toast.error((error as Error).message || "Unable to update email settings");
    }
  };

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-3xl font-bold">Email settings</h1>
        <p className="text-sm text-muted-foreground">
          Configure sender identity and delivery provider.
        </p>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Delivery configuration</CardTitle>
        </CardHeader>
        <CardContent className="space-y-4">
          <div className="space-y-2">
            <Label htmlFor="email-provider">Provider</Label>
            <select
              id="email-provider"
              value={provider}
              onChange={(event) => setProvider(event.target.value)}
              className="h-9 w-full rounded-md border border-input bg-background px-3 text-sm"
            >
              <option value="smtp">SMTP</option>
              <option value="mailgun">Mailgun</option>
              <option value="ses">Amazon SES</option>
              <option value="postmark">Postmark</option>
              <option value="sendmail">Sendmail</option>
            </select>
          </div>

          <div className="grid gap-4 md:grid-cols-2">
            <div className="space-y-2">
              <Label htmlFor="from-email">From email</Label>
              <Input
                id="from-email"
                value={fromEmail}
                onChange={(event) => setFromEmail(event.target.value)}
              />
            </div>
            <div className="space-y-2">
              <Label htmlFor="from-name">From name</Label>
              <Input
                id="from-name"
                value={fromName}
                onChange={(event) => setFromName(event.target.value)}
              />
            </div>
          </div>

          <div className="space-y-2">
            <Label htmlFor="reply-to">Reply-to</Label>
            <Input
              id="reply-to"
              value={replyTo}
              onChange={(event) => setReplyTo(event.target.value)}
            />
          </div>

          {provider === "smtp" && (
            <div className="grid gap-4 md:grid-cols-2">
              <div className="space-y-2">
                <Label htmlFor="smtp-host">SMTP host</Label>
                <Input
                  id="smtp-host"
                  value={smtpHost}
                  onChange={(event) => setSmtpHost(event.target.value)}
                />
              </div>
              <div className="space-y-2">
                <Label htmlFor="smtp-port">SMTP port</Label>
                <Input
                  id="smtp-port"
                  value={smtpPort}
                  onChange={(event) => setSmtpPort(event.target.value)}
                />
              </div>
              <div className="space-y-2">
                <Label htmlFor="smtp-user">SMTP username</Label>
                <Input
                  id="smtp-user"
                  value={smtpUsername}
                  onChange={(event) => setSmtpUsername(event.target.value)}
                />
              </div>
              <div className="space-y-2">
                <Label htmlFor="smtp-password">SMTP password</Label>
                <Input
                  id="smtp-password"
                  type="password"
                  value={smtpPassword}
                  onChange={(event) => setSmtpPassword(event.target.value)}
                />
              </div>
            </div>
          )}

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

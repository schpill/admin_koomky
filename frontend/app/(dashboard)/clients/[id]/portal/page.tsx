"use client";

import { FormEvent, useEffect, useState } from "react";
import { useParams } from "next/navigation";
import { toast } from "sonner";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Badge } from "@/components/ui/badge";
import { apiClient } from "@/lib/api";
import { useI18n } from "@/components/providers/i18n-provider";

interface PortalAccessToken {
  id: string;
  email: string;
  token: string;
  expires_at: string;
  is_active: boolean;
  last_used_at?: string | null;
  created_at: string;
}

interface PortalActivityLog {
  id: string;
  action: string;
  entity_type?: string | null;
  entity_id?: string | null;
  ip_address?: string | null;
  user_agent?: string | null;
  created_at: string;
}

interface PortalLogsResponse {
  data: PortalActivityLog[];
}

export default function ClientPortalAccessPage() {
  const { t } = useI18n();
  const params = useParams<{ id: string }>();
  const clientId = params.id;

  const [tokens, setTokens] = useState<PortalAccessToken[]>([]);
  const [logs, setLogs] = useState<PortalActivityLog[]>([]);
  const [isLoading, setLoading] = useState(true);

  const [email, setEmail] = useState("");
  const [expiresAt, setExpiresAt] = useState("");
  const [isCreating, setCreating] = useState(false);

  const load = async () => {
    if (!clientId) {
      return;
    }

    setLoading(true);
    try {
      const [tokensResponse, logsResponse] = await Promise.all([
        apiClient.get<PortalAccessToken[]>(
          `/clients/${clientId}/portal-access`
        ),
        apiClient.get<PortalLogsResponse>(
          `/clients/${clientId}/portal-activity`
        ),
      ]);

      setTokens(tokensResponse.data || []);
      setLogs(logsResponse.data?.data || []);
    } catch (error) {
      toast.error(
        (error as Error).message || t("clients.portal.toasts.linkFailed")
      );
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    load();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [clientId]);

  const createToken = async (event: FormEvent) => {
    event.preventDefault();
    if (!email.trim()) {
      toast.error(t("clients.portal.toasts.emailRequired"));
      return;
    }

    setCreating(true);
    try {
      await apiClient.post(`/clients/${clientId}/portal-access`, {
        email,
        expires_at: expiresAt || undefined,
      });
      setEmail("");
      setExpiresAt("");
      await load();
      toast.success(t("clients.portal.toasts.linkGenerated"));
    } catch (error) {
      toast.error(
        (error as Error).message || t("clients.portal.toasts.linkFailed")
      );
    } finally {
      setCreating(false);
    }
  };

  const revoke = async (tokenId: string) => {
    try {
      await apiClient.delete(`/clients/${clientId}/portal-access/${tokenId}`);
      await load();
      toast.success(t("clients.portal.toasts.revoked"));
    } catch (error) {
      toast.error(
        (error as Error).message || t("clients.portal.toasts.revokeFailed")
      );
    }
  };

  return (
    <div className="space-y-6">
      <div className="space-y-1">
        <h1 className="text-3xl font-bold">{t("clients.portal.title")}</h1>
        <p className="text-sm text-muted-foreground">
          {t("clients.portal.description")}
        </p>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>{t("clients.portal.generateAccess")}</CardTitle>
        </CardHeader>
        <CardContent>
          <form className="grid gap-3 md:grid-cols-3" onSubmit={createToken}>
            <div className="space-y-2 md:col-span-2">
              <Label htmlFor="portal-email">
                {t("clients.portal.contactEmail")}
              </Label>
              <Input
                id="portal-email"
                type="email"
                value={email}
                onChange={(event) => setEmail(event.target.value)}
                placeholder="client@company.com"
                required
              />
            </div>
            <div className="space-y-2">
              <Label htmlFor="portal-expires-at">
                {t("clients.portal.expiresAt")}
              </Label>
              <Input
                id="portal-expires-at"
                type="date"
                value={expiresAt}
                onChange={(event) => setExpiresAt(event.target.value)}
              />
            </div>
            <div className="md:col-span-3">
              <Button type="submit" disabled={isCreating}>
                {isCreating
                  ? t("clients.portal.generating")
                  : t("clients.portal.generateLink")}
              </Button>
            </div>
          </form>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>{t("clients.portal.activeTokens")}</CardTitle>
        </CardHeader>
        <CardContent>
          {isLoading ? (
            <p className="text-sm text-muted-foreground">
              {t("clients.portal.loadingTokens")}
            </p>
          ) : tokens.length === 0 ? (
            <p className="text-sm text-muted-foreground">
              {t("clients.portal.noTokens")}
            </p>
          ) : (
            <div className="space-y-2">
              {tokens.map((token) => (
                <div
                  key={token.id}
                  className="flex flex-wrap items-center justify-between gap-3 rounded-md border p-3"
                >
                  <div>
                    <p className="font-medium">{token.email}</p>
                    <p className="text-xs text-muted-foreground">
                      {t("clients.portal.expires")}{" "}
                      {new Date(token.expires_at).toLocaleString()} ·
                      {t("clients.portal.lastUsed")}{" "}
                      {token.last_used_at
                        ? new Date(token.last_used_at).toLocaleString()
                        : "never"}
                    </p>
                  </div>
                  <div className="flex items-center gap-2">
                    <Badge variant={token.is_active ? "default" : "outline"}>
                      {token.is_active
                        ? t("clients.portal.active")
                        : t("clients.portal.revoked")}
                    </Badge>
                    {token.is_active ? (
                      <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        onClick={() => revoke(token.id)}
                      >
                        {t("clients.portal.revoke")}
                      </Button>
                    ) : null}
                  </div>
                </div>
              ))}
            </div>
          )}
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>{t("clients.portal.activityLog")}</CardTitle>
        </CardHeader>
        <CardContent>
          {isLoading ? (
            <p className="text-sm text-muted-foreground">
              {t("clients.portal.loadingActivity")}
            </p>
          ) : logs.length === 0 ? (
            <p className="text-sm text-muted-foreground">
              {t("clients.portal.noActivity")}
            </p>
          ) : (
            <div className="space-y-2">
              {logs.map((log) => (
                <div key={log.id} className="rounded-md border p-3 text-sm">
                  <p className="font-medium">{log.action}</p>
                  <p className="text-xs text-muted-foreground">
                    {new Date(log.created_at).toLocaleString()} ·{" "}
                    {log.ip_address || "unknown IP"}
                  </p>
                </div>
              ))}
            </div>
          )}
        </CardContent>
      </Card>
    </div>
  );
}

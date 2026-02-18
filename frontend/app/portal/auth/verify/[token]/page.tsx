"use client";

import { useEffect, useState } from "react";
import Link from "next/link";
import { useParams, useRouter } from "next/navigation";
import { AlertCircle, CheckCircle2 } from "lucide-react";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { portalApiClient, savePortalSession } from "@/lib/portal";

interface VerifyResponse {
  portal_token: string;
  expires_in: number;
  client: {
    id: string;
    name: string;
    email?: string | null;
  };
}

export default function PortalVerifyTokenPage() {
  const params = useParams<{ token: string }>();
  const router = useRouter();

  const [status, setStatus] = useState<"loading" | "success" | "error">(
    "loading"
  );
  const [message, setMessage] = useState("Verifying your access token...");

  useEffect(() => {
    const token = params.token;

    if (!token) {
      setStatus("error");
      setMessage("Missing token.");
      return;
    }

    portalApiClient
      .get<VerifyResponse>(`/portal/auth/verify/${token}`, { skipAuth: true })
      .then((response) => {
        const payload = response.data;
        savePortalSession({
          portal_token: payload.portal_token,
          expires_in: payload.expires_in,
          client: payload.client,
        });
        setStatus("success");
        setMessage("Access granted. Redirecting to your portal...");
        window.setTimeout(() => {
          router.replace("/portal/dashboard");
        }, 750);
      })
      .catch((error) => {
        setStatus("error");
        setMessage((error as Error).message || "Token verification failed.");
      });
  }, [params.token, router]);

  return (
    <Card className="mt-8">
      <CardHeader>
        <CardTitle>Portal verification</CardTitle>
      </CardHeader>
      <CardContent className="space-y-4">
        <div className="rounded-lg border bg-muted/40 p-4 text-sm">
          <p className="inline-flex items-center gap-2">
            {status === "success" ? (
              <CheckCircle2 className="h-4 w-4 text-emerald-600" />
            ) : status === "error" ? (
              <AlertCircle className="h-4 w-4 text-destructive" />
            ) : (
              <span className="h-4 w-4 animate-spin rounded-full border-2 border-primary border-t-transparent" />
            )}
            {message}
          </p>
        </div>

        {status === "error" ? (
          <Button asChild variant="outline" className="w-full">
            <Link href="/portal/auth">Request a new magic link</Link>
          </Button>
        ) : null}
      </CardContent>
    </Card>
  );
}

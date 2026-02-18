"use client";

import { FormEvent, useState } from "react";
import { MailCheck } from "lucide-react";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Label } from "@/components/ui/label";
import { Input } from "@/components/ui/input";
import { Button } from "@/components/ui/button";
import { portalApiClient } from "@/lib/portal";

export default function PortalAuthPage() {
  const [email, setEmail] = useState("");
  const [isSubmitting, setSubmitting] = useState(false);
  const [feedback, setFeedback] = useState<string | null>(null);

  const onSubmit = async (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    if (!email.trim()) {
      setFeedback("Please provide a valid email.");
      return;
    }

    setSubmitting(true);
    try {
      const response = await portalApiClient.post<null>(
        "/portal/auth/request",
        { email },
        { skipAuth: true }
      );
      setFeedback(
        response.message ||
          "If this email exists, a magic access link has been sent."
      );
    } catch (error) {
      setFeedback((error as Error).message);
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <Card className="mt-8">
      <CardHeader>
        <CardTitle>Client Portal Access</CardTitle>
      </CardHeader>
      <CardContent className="space-y-4">
        <p className="text-sm text-muted-foreground">
          Enter your email to receive a secure magic link for your portal.
        </p>
        <form className="space-y-3" onSubmit={onSubmit}>
          <div className="space-y-2">
            <Label htmlFor="portal-email">Email</Label>
            <Input
              id="portal-email"
              type="email"
              value={email}
              onChange={(event) => setEmail(event.target.value)}
              placeholder="client@company.com"
              required
            />
          </div>
          <Button className="w-full" disabled={isSubmitting}>
            {isSubmitting ? "Sending..." : "Send magic link"}
          </Button>
        </form>

        {feedback ? (
          <div className="rounded-lg border bg-muted/40 p-3 text-sm">
            <p className="inline-flex items-center gap-2">
              <MailCheck className="h-4 w-4" />
              {feedback}
            </p>
          </div>
        ) : null}
      </CardContent>
    </Card>
  );
}

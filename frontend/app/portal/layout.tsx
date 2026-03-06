"use client";

import { useEffect, useState } from "react";
import { usePathname, useRouter } from "next/navigation";
import { PortalHeader } from "@/components/portal/portal-header";
import { ChatWidget } from "@/components/rag/chat-widget";
import { useRagStore } from "@/lib/stores/rag";
import {
  clearPortalSession,
  getPortalSession,
  portalApiClient,
  savePortalSession,
  type PortalSession,
} from "@/lib/portal";

interface PortalDashboardBrandingPayload {
  client?: {
    id: string;
    name: string;
    email?: string | null;
  };
  branding?: {
    custom_logo?: string | null;
    custom_color?: string | null;
  };
}

export default function PortalLayout({
  children,
}: Readonly<{ children: React.ReactNode }>) {
  const pathname = usePathname();
  const router = useRouter();
  const isAuthRoute = pathname.startsWith("/portal/auth");
  const isSignedPreferenceRoute = pathname.startsWith("/portal/preferences/");

  const [session, setSession] = useState<PortalSession | null>(null);
  const [branding, setBranding] = useState<
    PortalDashboardBrandingPayload["branding"] | null
  >(null);
  const [ragAvailable, setRagAvailable] = useState(false);
  const sessionExpiresAt = session?.expires_at;

  useEffect(() => {
    const currentSession = getPortalSession();

    if (isAuthRoute || isSignedPreferenceRoute) {
      setSession(currentSession);
      return;
    }

    if (!currentSession) {
      router.replace("/portal/auth");
      return;
    }

    setSession(currentSession);
  }, [isAuthRoute, isSignedPreferenceRoute, pathname, router]);

  useEffect(() => {
    if (isAuthRoute || isSignedPreferenceRoute || !session?.portal_token) {
      return;
    }

    let cancelled = false;

    portalApiClient
      .get<PortalDashboardBrandingPayload>("/portal/dashboard")
      .then((response) => {
        if (cancelled) {
          return;
        }

        setBranding(response.data?.branding || null);

        if (response.data?.client && typeof sessionExpiresAt === "number") {
          savePortalSession({
            portal_token: session.portal_token,
            expires_in: Math.max(
              1,
              Math.floor((sessionExpiresAt - Date.now()) / 1000)
            ),
            client: response.data.client,
          });
          setSession(getPortalSession());
        }
      })
      .catch(() => {
        // Keep shell available even if dashboard prefetch fails.
      });

    return () => {
      cancelled = true;
    };
  }, [isAuthRoute, isSignedPreferenceRoute, session?.portal_token, sessionExpiresAt]);

  useEffect(() => {
    if (isAuthRoute || isSignedPreferenceRoute || !session?.portal_token) {
      return;
    }

    portalApiClient
      .get<{ available: boolean; indexed_documents: number }>(
        "/portal/rag/status"
      )
      .then((response) => {
        setRagAvailable(Boolean(response.data?.available));
      })
      .catch(() => {
        setRagAvailable(false);
      });
  }, [isAuthRoute, isSignedPreferenceRoute, session?.portal_token]);

  const logout = async () => {
    const clearRagHistory = useRagStore.getState().clearHistory;
    try {
      await portalApiClient.post("/portal/auth/logout");
    } catch {
      // Session cleanup still occurs even if API fails.
    }

    clearRagHistory();
    clearPortalSession();
    setSession(null);
    router.push("/portal/auth");
  };

  if (isAuthRoute || isSignedPreferenceRoute) {
    return (
      <main className="min-h-screen bg-background px-4 py-8 sm:px-6 lg:px-8">
        <div className="mx-auto w-full max-w-lg">{children}</div>
      </main>
    );
  }

  if (!session) {
    return (
      <main className="min-h-screen bg-background p-6">
        <div className="mx-auto w-full max-w-4xl rounded-xl border bg-card p-6 text-sm text-muted-foreground">
          Loading portal...
        </div>
      </main>
    );
  }

  return (
    <div className="min-h-screen bg-background">
      <PortalHeader
        clientName={session.client?.name}
        customLogo={branding?.custom_logo}
        customColor={branding?.custom_color}
        onLogout={logout}
      />
      <main className="mx-auto w-full max-w-6xl space-y-6 px-4 py-6">
        {children}
      </main>
      {ragAvailable ? <ChatWidget portalMode /> : null}
    </div>
  );
}

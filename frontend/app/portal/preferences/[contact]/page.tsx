"use client";

import { useEffect, useState } from "react";
import { useParams, useSearchParams } from "next/navigation";
import { toast } from "sonner";
import {
  PreferenceCenterForm,
  type PreferenceItem,
} from "@/components/portal/preference-center-form";

interface PreferenceResponse {
  contact_id: string;
  preferences: PreferenceItem[];
}

function backendBaseUrl(): string {
  const apiBase =
    process.env.NEXT_PUBLIC_API_URL ||
    (typeof window !== "undefined"
      ? `${window.location.origin}/api/v1`
      : "http://localhost/api/v1");
  return apiBase.replace(/\/api\/v1$/, "");
}

export default function PortalPreferenceCenterPage() {
  const params = useParams<{ contact: string }>();
  const searchParams = useSearchParams();
  const [payload, setPayload] = useState<PreferenceResponse | null>(null);
  const cacheKey = `koomky-portal-preferences-${params.contact}`;

  useEffect(() => {
    if (typeof window !== "undefined") {
      const cached = window.localStorage.getItem(cacheKey);
      if (cached) {
        try {
          setPayload(JSON.parse(cached) as PreferenceResponse);
        } catch {
          window.localStorage.removeItem(cacheKey);
        }
      }
    }

    const load = async () => {
      const query = searchParams.toString();
      const response = await fetch(
        `${backendBaseUrl()}/portal/preferences/${params.contact}?${query}`,
        {
          headers: { Accept: "application/json" },
        }
      );

      if (!response.ok) {
        throw new Error("Unable to load preferences");
      }

      const json = await response.json();
      setPayload(json.data);
      if (typeof window !== "undefined") {
        window.localStorage.setItem(cacheKey, JSON.stringify(json.data));
      }
    };

    load().catch((error) => {
      toast.error((error as Error).message);
    });
  }, [cacheKey, params.contact, searchParams]);

  if (!payload) {
    return <p className="text-sm text-muted-foreground">Loading preferences...</p>;
  }

  return (
    <PreferenceCenterForm
      initialPreferences={payload.preferences}
      onSubmit={async (preferences) => {
        const query = searchParams.toString();
        const response = await fetch(
          `${backendBaseUrl()}/portal/preferences/${params.contact}?${query}`,
          {
            method: "POST",
            headers: {
              "Content-Type": "application/json",
              Accept: "application/json",
            },
            body: JSON.stringify({ preferences }),
          }
        );

        if (!response.ok) {
          throw new Error("Unable to save preferences");
        }

        const json = await response.json();
        setPayload(json.data);
        if (typeof window !== "undefined") {
          window.localStorage.setItem(cacheKey, JSON.stringify(json.data));
        }
        toast.success("Preferences updated");
      }}
    />
  );
}

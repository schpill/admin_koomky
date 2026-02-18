"use client";

import { useEffect } from "react";
import { useRouter } from "next/navigation";
import { getPortalSession } from "@/lib/portal";

export default function PortalIndexPage() {
  const router = useRouter();

  useEffect(() => {
    if (getPortalSession()) {
      router.replace("/portal/dashboard");
      return;
    }

    router.replace("/portal/auth");
  }, [router]);

  return <p className="text-sm text-muted-foreground">Redirecting...</p>;
}

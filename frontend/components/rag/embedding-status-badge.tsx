"use client";

import { Loader2 } from "lucide-react";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";

interface Props {
  status: string | null;
  onRetry?: () => void;
}

export function EmbeddingStatusBadge({ status, onRetry }: Props) {
  if (status === "indexed") {
    return <Badge className="bg-green-600 text-white">Indexed</Badge>;
  }

  if (status === "pending" || status === "indexing") {
    return (
      <Badge className="bg-orange-500 text-white inline-flex items-center gap-1">
        <Loader2 className="h-3 w-3 animate-spin" />
        {status}
      </Badge>
    );
  }

  if (status === "failed") {
    return (
      <div className="flex items-center gap-2">
        <Badge variant="destructive">Failed</Badge>
        {onRetry ? (
          <Button size="sm" variant="outline" onClick={onRetry}>
            Relancer l&apos;indexation
          </Button>
        ) : null}
      </div>
    );
  }

  return <Badge variant="secondary">Non indexable</Badge>;
}

"use client";

import { useEffect, useState } from "react";
import { toast } from "sonner";
import { DashboardLayout } from "@/components/layout/dashboard-layout";
import { apiClient } from "@/lib/api";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Card, CardHeader, CardTitle, CardContent } from "@/components/ui/card";
import { EmbeddingStatusBadge } from "@/components/rag/embedding-status-badge";

export default function RagSettingsPage() {
  const [rows, setRows] = useState<any[]>([]);
  const [q, setQ] = useState("");
  const [loading, setLoading] = useState(false);

  const fetchRows = async () => {
    setLoading(true);
    try {
      const response = await apiClient.get<any>("/rag/status");
      setRows(response.data.data || []);
    } catch (error) {
      toast.error("Impossible de charger le statut RAG");
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    void fetchRows();
  }, []);

  const retry = async (id: string) => {
    try {
      await apiClient.post(`/rag/reindex/${id}`);
      toast.success("Ré-indexation lancée");
      void fetchRows();
    } catch (error) {
      toast.error("Échec de la ré-indexation");
    }
  };

  const filtered = rows.filter((row) => {
    if (!q.trim()) return true;
    return String(row.title || "")
      .toLowerCase()
      .includes(q.toLowerCase());
  });

  const indexed = rows.filter((r) => r.embedding_status === "indexed").length;

  return (
    <DashboardLayout>
      <div className="space-y-6">
        <div>
          <h1 className="text-3xl font-bold">Intelligence documentaire</h1>
          <p className="text-sm text-muted-foreground">
            Statut d'indexation et administration RAG
          </p>
        </div>

        <div className="grid grid-cols-1 gap-4 md:grid-cols-3">
          <Card>
            <CardHeader>
              <CardTitle>Documents indexés</CardTitle>
            </CardHeader>
            <CardContent>
              {indexed}/{rows.length}
            </CardContent>
          </Card>
          <Card>
            <CardHeader>
              <CardTitle>Chunks estimés</CardTitle>
            </CardHeader>
            <CardContent>{rows.length * 3}</CardContent>
          </Card>
          <Card>
            <CardHeader>
              <CardTitle>Dernière synchro</CardTitle>
            </CardHeader>
            <CardContent>{new Date().toLocaleString()}</CardContent>
          </Card>
        </div>

        <Card>
          <CardHeader>
            <CardTitle>Documents</CardTitle>
          </CardHeader>
          <CardContent className="space-y-4">
            <Input
              value={q}
              onChange={(event) => setQ(event.target.value)}
              placeholder="Rechercher un document..."
            />
            {loading ? (
              <p className="text-sm text-muted-foreground">Chargement...</p>
            ) : null}
            <div className="space-y-3">
              {filtered.map((row) => (
                <div
                  key={row.id}
                  className="flex items-center justify-between rounded-lg border p-3"
                >
                  <div>
                    <p className="font-medium">{row.title}</p>
                    <p className="text-xs text-muted-foreground">
                      {row.mime_type}
                    </p>
                  </div>
                  <EmbeddingStatusBadge
                    status={row.embedding_status}
                    onRetry={() => void retry(row.id)}
                  />
                </div>
              ))}
            </div>
            <div className="flex justify-end">
              <Button
                onClick={async () => {
                  for (const row of filtered) {
                    await retry(row.id);
                  }
                }}
              >
                Re-indexer tout
              </Button>
            </div>
          </CardContent>
        </Card>
      </div>
    </DashboardLayout>
  );
}

"use client";

import { useState, useEffect } from "react";
import { 
  FileText, 
  Download, 
  Loader2, 
  AlertCircle,
  FileSearch,
  ExternalLink
} from "lucide-react";
import { Button } from "@/components/ui/button";
import { Skeleton } from "@/components/ui/skeleton";
import { Badge } from "@/components/ui/badge";
import { type Document, useDocumentStore } from "@/lib/stores/documents";
import { apiClient } from "@/lib/api";

interface DocumentPreviewProps {
  document: Document;
}

export function DocumentPreview({ document }: DocumentPreviewProps) {
  const { downloadDocument } = useDocumentStore();
  const [content, setContent] = useState<string | null>(null);
  const [imageUrl, setImageUrl] = useState<string | null>(null);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    const fetchPreview = async () => {
      setIsLoading(true);
      setError(null);
      setContent(null);
      setImageUrl(null);

      try {
        if (document.document_type === "image") {
          const response = await apiClient.get<Blob>(`/documents/${document.id}/download`, {
            params: { inline: 1 },
            responseType: "blob",
          });
          const url = URL.createObjectURL(new Blob([response.data as any]));
          setImageUrl(url);
        } else if (document.document_type === "text" || document.document_type === "script") {
          const response = await apiClient.get<any>(`/documents/${document.id}/download`, {
            params: { inline: 1 },
            responseType: "text",
          });
          // If response.data is a Blob (due to interceptors or defaults), we need to read it as text
          if (response.data instanceof Blob) {
            const text = await (response.data as Blob).text();
            setContent(text);
          } else if (typeof response.data === 'string') {
            setContent(response.data);
          } else {
            setContent(JSON.stringify(response.data, null, 2));
          }
        }
      } catch (err: any) {
        console.error("Preview error:", err);
        setError("Impossible de charger la prévisualisation.");
      } finally {
        setIsLoading(false);
      }
    };

    if (["image", "text", "script"].includes(document.document_type)) {
      fetchPreview();
    } else {
      setIsLoading(false);
    }

    return () => {
      if (imageUrl) URL.revokeObjectURL(imageUrl);
    };
  }, [document.id, document.document_type]);

  const handleDownload = () => {
    downloadDocument(document.id);
  };

  if (isLoading) {
    return (
      <div className="flex h-[400px] w-full flex-col gap-4 items-center justify-center rounded-lg border bg-muted/20">
        <Loader2 className="h-8 w-8 animate-spin text-muted-foreground" />
        <p className="text-sm text-muted-foreground">Chargement de l&apos;aperçu...</p>
      </div>
    );
  }

  if (error) {
    return (
      <div className="flex h-[400px] w-full flex-col gap-4 items-center justify-center rounded-lg border border-destructive/20 bg-destructive/5 text-destructive">
        <AlertCircle className="h-8 w-8" />
        <p className="text-sm font-medium">{error}</p>
        <Button variant="outline" size="sm" onClick={() => window.location.reload()}>
          Réessayer
        </Button>
      </div>
    );
  }

  if (document.document_type === "pdf") {
    // For PDF, we use an iframe directly to the download endpoint with inline=1
    // We need to pass the auth token if required, but since it's an iframe, 
    // it's better to use a blob URL like images if auth is needed.
    // However, for simplicity and performance, many browsers handle PDFs well.
    // Let's use the blob approach for consistency with auth.
    return (
      <div className="h-[600px] w-full rounded-lg border overflow-hidden">
        <iframe 
          src={`${process.env.NEXT_PUBLIC_API_URL}/documents/${document.id}/download?inline=1`}
          className="h-full w-full"
          title={document.title}
        />
      </div>
    );
  }

  if (document.document_type === "image" && imageUrl) {
    return (
      <div className="flex min-h-[400px] w-full items-center justify-center rounded-lg border bg-muted/10 p-4">
        <img 
          src={imageUrl} 
          alt={document.title} 
          className="max-h-[600px] max-w-full rounded-md shadow-sm object-contain"
        />
      </div>
    );
  }

  if ((document.document_type === "text" || document.document_type === "script") && content !== null) {
    return (
      <div className="relative h-[600px] w-full rounded-lg border bg-zinc-950 text-zinc-50 overflow-hidden">
        <div className="absolute right-4 top-4 z-10 flex gap-2">
          <Badge variant="outline" className="bg-zinc-900 text-zinc-400 border-zinc-800">
            {document.script_language || "text"}
          </Badge>
        </div>
        <pre className="h-full w-full overflow-auto p-6 font-mono text-sm leading-relaxed scrollbar-thin scrollbar-thumb-zinc-800">
          <code>{content}</code>
        </pre>
      </div>
    );
  }

  return (
    <div className="flex h-[400px] w-full flex-col gap-6 items-center justify-center rounded-lg border bg-muted/20 p-8 text-center">
      <div className="rounded-full bg-muted p-6">
        <FileSearch className="h-12 w-12 text-muted-foreground" />
      </div>
      <div className="max-w-[300px] space-y-2">
        <h3 className="font-semibold">Aperçu non disponible</h3>
        <p className="text-sm text-muted-foreground">
          La prévisualisation n&apos;est pas disponible pour ce type de fichier ({document.document_type}).
        </p>
      </div>
      <div className="flex gap-3">
        <Button onClick={handleDownload} className="gap-2">
          <Download className="h-4 w-4" />
          Télécharger le fichier
        </Button>
        <Button variant="outline" asChild className="gap-2">
          <a 
            href={`${process.env.NEXT_PUBLIC_API_URL}/documents/${document.id}/download?inline=1`} 
            target="_blank" 
            rel="noopener noreferrer"
          >
            <ExternalLink className="h-4 w-4" />
            Ouvrir dans un nouvel onglet
          </a>
        </Button>
      </div>
    </div>
  );
}

"use client";

import { useEffect } from "react";
import { 
  FileText, 
  ArrowRight, 
  ExternalLink,
  Loader2,
  Files
} from "lucide-react";
import { useDocumentStore } from "@/lib/stores/documents";
import { DocumentTypeBadge } from "@/components/documents/document-type-badge";
import { 
  Card, 
  CardContent, 
  CardHeader, 
  CardTitle,
  CardFooter
} from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { formatDate } from "@/lib/utils";
import Link from "next/link";

export function RecentDocumentsWidget() {
  const { documents, fetchDocuments, isLoading } = useDocumentStore();

  useEffect(() => {
    fetchDocuments({ per_page: 5, sort_by: "created_at", sort_order: "desc" });
  }, [fetchDocuments]);

  const recentDocs = documents.slice(0, 5);

  return (
    <Card className="flex flex-col h-full shadow-sm hover:shadow-md transition-shadow">
      <CardHeader className="flex flex-row items-center justify-between pb-2">
        <CardTitle className="text-lg font-bold flex items-center gap-2">
          <Files className="h-5 w-5 text-primary" />
          Documents récents
        </CardTitle>
        <Button variant="ghost" size="sm" asChild className="text-xs h-8">
          <Link href="/documents" className="gap-1">
            Voir tout
            <ArrowRight className="h-3 w-3" />
          </Link>
        </Button>
      </CardHeader>
      <CardContent className="flex-1 px-2">
        {isLoading && documents.length === 0 ? (
          <div className="flex flex-col gap-4 p-4 items-center justify-center h-48">
            <Loader2 className="h-6 w-6 animate-spin text-muted-foreground" />
            <p className="text-xs text-muted-foreground">Chargement des documents...</p>
          </div>
        ) : recentDocs.length === 0 ? (
          <div className="flex flex-col items-center justify-center h-48 text-center p-6 border-2 border-dashed border-muted rounded-lg m-4">
            <p className="text-sm text-muted-foreground italic">Aucun document importé.</p>
            <Button variant="link" size="sm" asChild className="mt-2 text-xs">
              <Link href="/documents">Importer le premier document</Link>
            </Button>
          </div>
        ) : (
          <div className="space-y-1">
            {recentDocs.map((doc) => (
              <Link 
                key={doc.id} 
                href={`/documents/${doc.id}`}
                className="flex items-center gap-3 p-3 rounded-md hover:bg-accent/50 transition-colors group"
              >
                <div className="rounded-md bg-muted p-2 group-hover:bg-background transition-colors">
                  <DocumentTypeBadge 
                    type={doc.document_type} 
                    showLabel={false} 
                    className="p-0 bg-transparent dark:bg-transparent"
                  />
                </div>
                <div className="flex-1 min-w-0">
                  <p className="text-sm font-medium line-clamp-1 group-hover:underline">
                    {doc.title}
                  </p>
                  <p className="text-[10px] text-muted-foreground">
                    {formatDate(doc.created_at)}
                  </p>
                </div>
                <ExternalLink className="h-3 w-3 text-muted-foreground opacity-0 group-hover:opacity-100 transition-opacity" />
              </Link>
            ))}
          </div>
        )}
      </CardContent>
    </Card>
  );
}

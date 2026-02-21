"use client";

import { useEffect, useState } from "react";
import { 
  LayoutGrid, 
  List, 
  Trash2, 
  FileText, 
  HardDrive,
  AlertCircle
} from "lucide-react";
import { useDocumentStore } from "@/lib/stores/documents";
import { DocumentCard } from "@/components/documents/document-card";
import { DocumentFilters } from "@/components/documents/document-filters";
import { DocumentUploadDialog } from "@/components/documents/document-upload-dialog";
import { DocumentSendEmailDialog } from "@/components/documents/document-send-email-dialog";
import { Button } from "@/components/ui/button";
import { Separator } from "@/components/ui/separator";
import { Tabs, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { Checkbox } from "@/components/ui/checkbox";
import { Badge } from "@/components/ui/badge";
import { Skeleton } from "@/components/ui/skeleton";
import { 
  Table, 
  TableBody, 
  TableCell, 
  TableHead, 
  TableHeader, 
  TableRow 
} from "@/components/ui/table";
import { formatBytes, formatDate } from "@/lib/utils";
import { DocumentTypeBadge } from "@/components/documents/document-type-badge";
import { 
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
} from "@/components/ui/alert-dialog";
import { toast } from "sonner";

export default function DocumentsPage() {
  const { 
    documents, 
    isLoading, 
    fetchDocuments, 
    fetchStats, 
    stats,
    downloadDocument,
    deleteDocument,
    bulkDelete
  } = useDocumentStore();

  const [view, setView] = useState<"grid" | "list">("grid");
  const [selectedIds, setSelectedIds] = useState<string[]>([]);
  const [isDeleteDialogOpen, setIsDeleteDialogOpen] = useState(false);
  const [isBulkDeleteDialogOpen, setIsBulkDeleteDialogOpen] = useState(false);
  const [documentToDelete, setDocumentToDelete] = useState<string | null>(null);
  const [documentToEmail, setDocumentToEmail] = useState<any>(null);
  const [isEmailDialogOpen, setIsEmailDialogOpen] = useState(false);

  useEffect(() => {
    fetchDocuments();
    fetchStats();
  }, [fetchDocuments, fetchStats]);

  const toggleSelect = (id: string) => {
    setSelectedIds(prev => 
      prev.includes(id) ? prev.filter(i => i !== id) : [...prev, id]
    );
  };

  const selectAll = () => {
    if (selectedIds.length === documents.length) {
      setSelectedIds([]);
    } else {
      setSelectedIds(documents.map(d => d.id));
    }
  };

  const handleDelete = async () => {
    if (!documentToDelete) return;
    try {
      await deleteDocument(documentToDelete);
      toast.success("Document supprimé");
      setIsDeleteDialogOpen(false);
      fetchStats();
    } catch (error: any) {
      toast.error(error.message);
    }
  };

  const handleBulkDelete = async () => {
    try {
      await bulkDelete(selectedIds);
      toast.success(`${selectedIds.length} documents supprimés`);
      setSelectedIds([]);
      setIsBulkDeleteDialogOpen(false);
      fetchStats();
    } catch (error: any) {
      toast.error(error.message);
    }
  };

  return (
    <div className="flex h-full flex-col space-y-6 p-8">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold tracking-tight">Documents</h1>
          <p className="text-muted-foreground">
            Gérez et stockez vos documents en toute sécurité.
          </p>
        </div>
        <DocumentUploadDialog />
      </div>

      <div className="grid grid-cols-1 gap-4 md:grid-cols-3 lg:grid-cols-4">
        <div className="rounded-xl border bg-card p-4 shadow-sm">
          <div className="flex items-center gap-3">
            <div className="rounded-lg bg-blue-100 p-2 dark:bg-blue-900/30">
              <FileText className="h-5 w-5 text-blue-600 dark:text-blue-400" />
            </div>
            <div>
              <p className="text-sm font-medium text-muted-foreground text-[10px] uppercase tracking-wider">
                Total Documents
              </p>
              <p className="text-2xl font-bold">{stats?.total_count || 0}</p>
            </div>
          </div>
        </div>

        <div className="col-span-1 rounded-xl border bg-card p-4 shadow-sm md:col-span-2 lg:col-span-3">
          <div className="flex flex-col gap-3">
            <div className="flex items-center justify-between">
              <div className="flex items-center gap-3">
                <div className="rounded-lg bg-purple-100 p-2 dark:bg-purple-900/30">
                  <HardDrive className="h-5 w-5 text-purple-600 dark:text-purple-400" />
                </div>
                <div>
                  <p className="text-sm font-medium text-muted-foreground text-[10px] uppercase tracking-wider">
                    Stockage utilisé
                  </p>
                  <p className="text-lg font-bold">
                    {formatBytes(stats?.total_size_bytes || 0)} / {formatBytes(stats?.quota_bytes || 0)}
                  </p>
                </div>
              </div>
              <span className="text-sm font-medium">{stats?.usage_percentage || 0}%</span>
            </div>
            <div className="h-2 w-full overflow-hidden rounded-full bg-muted">
              <div 
                className="h-full bg-purple-500 transition-all duration-500" 
                style={{ width: `${Math.min(stats?.usage_percentage || 0, 100)}%` }}
              />
            </div>
          </div>
        </div>
      </div>

      <div className="flex flex-1 flex-col gap-6 lg:flex-row">
        <aside className="w-full lg:w-64">
          <div className="sticky top-8 space-y-6">
            <DocumentFilters />
          </div>
        </aside>

        <main className="flex-1 space-y-4">
          <div className="flex items-center justify-between">
            <div className="flex items-center gap-4">
              <Tabs value={view} onValueChange={(v) => setView(v as any)} className="w-auto">
                <TabsList>
                  <TabsTrigger value="grid" className="gap-2">
                    <LayoutGrid className="h-4 w-4" />
                    Grille
                  </TabsTrigger>
                  <TabsTrigger value="list" className="gap-2">
                    <List className="h-4 w-4" />
                    Liste
                  </TabsTrigger>
                </TabsList>
              </Tabs>
              
              {selectedIds.length > 0 && (
                <div className="flex items-center gap-2 animate-in fade-in slide-in-from-left-4">
                  <Separator orientation="vertical" className="h-8" />
                  <span className="text-sm font-medium text-muted-foreground px-2">
                    {selectedIds.length} sélectionné(s)
                  </span>
                  <Button 
                    variant="destructive" 
                    size="sm" 
                    className="h-8 gap-2"
                    onClick={() => setIsBulkDeleteDialogOpen(true)}
                  >
                    <Trash2 className="h-4 w-4" />
                    Supprimer
                  </Button>
                </div>
              )}
            </div>

            <Button variant="outline" size="sm" onClick={selectAll}>
              {selectedIds.length === documents.length && documents.length > 0 
                ? "Tout désélectionner" 
                : "Tout sélectionner"}
            </Button>
          </div>

          {isLoading && documents.length === 0 ? (
            <div className={view === "grid" ? "grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4" : "space-y-2"}>
              {Array.from({ length: 8 }).map((_, i) => (
                <Skeleton key={i} className={view === "grid" ? "h-48 w-full rounded-xl" : "h-12 w-full"} />
              ))}
            </div>
          ) : documents.length === 0 ? (
            <div className="flex h-96 flex-col items-center justify-center rounded-xl border border-dashed text-center p-8">
              <div className="rounded-full bg-muted p-6 mb-4">
                <FileText className="h-12 w-12 text-muted-foreground" />
              </div>
              <h3 className="text-lg font-semibold">Aucun document trouvé</h3>
              <p className="text-muted-foreground max-w-sm mt-2">
                Commencez par importer un document ou modifiez vos filtres de recherche.
              </p>
              <div className="mt-6">
                <DocumentUploadDialog />
              </div>
            </div>
          ) : view === "grid" ? (
            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
              {documents.map((doc) => (
                <DocumentCard 
                  key={doc.id} 
                  document={doc} 
                  isSelected={selectedIds.includes(doc.id)}
                  onSelect={() => toggleSelect(doc.id)}
                  onDownload={() => downloadDocument(doc.id)}
                  onSendEmail={() => {
                    setDocumentToEmail(doc);
                    setIsEmailDialogOpen(true);
                  }}
                  onDelete={() => {
                    setDocumentToDelete(doc.id);
                    setIsDeleteDialogOpen(true);
                  }}
                />
              ))}
            </div>
          ) : (
            <div className="rounded-md border bg-card shadow-sm overflow-hidden">
              <Table>
                <TableHeader>
                  <TableRow className="bg-muted/50">
                    <TableHead className="w-12 text-center">
                      <Checkbox 
                        checked={selectedIds.length === documents.length && documents.length > 0}
                        onCheckedChange={selectAll}
                      />
                    </TableHead>
                    <TableHead>Titre</TableHead>
                    <TableHead>Type</TableHead>
                    <TableHead>Client</TableHead>
                    <TableHead>Taille</TableHead>
                    <TableHead>Date</TableHead>
                    <TableHead className="text-right">Actions</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {documents.map((doc) => (
                    <TableRow key={doc.id} className="group transition-colors hover:bg-muted/50">
                      <TableCell className="text-center">
                        <Checkbox 
                          checked={selectedIds.includes(doc.id)}
                          onCheckedChange={() => toggleSelect(doc.id)}
                        />
                      </TableCell>
                      <TableCell className="font-medium">
                        <div className="flex flex-col">
                          <span className="line-clamp-1">{doc.title}</span>
                          <span className="text-[10px] text-muted-foreground line-clamp-1">{doc.original_filename}</span>
                        </div>
                      </TableCell>
                      <TableCell>
                        <DocumentTypeBadge type={doc.document_type} scriptLanguage={doc.script_language} />
                      </TableCell>
                      <TableCell>
                        {doc.client ? (
                          <Badge variant="outline" className="font-normal">{doc.client.name}</Badge>
                        ) : (
                          <span className="text-muted-foreground">—</span>
                        )}
                      </TableCell>
                      <TableCell className="text-xs text-muted-foreground">
                        {formatBytes(doc.file_size)}
                      </TableCell>
                      <TableCell className="text-xs text-muted-foreground">
                        {formatDate(doc.created_at)}
                      </TableCell>
                      <TableCell className="text-right">
                        <div className="flex justify-end gap-2">
                          <Button variant="ghost" size="icon" className="h-8 w-8 opacity-0 group-hover:opacity-100" onClick={() => downloadDocument(doc.id)}>
                            <Download className="h-4 w-4" />
                          </Button>
                          <Button variant="ghost" size="icon" className="h-8 w-8 opacity-0 group-hover:opacity-100" onClick={() => {
                            setDocumentToEmail(doc);
                            setIsEmailDialogOpen(true);
                          }}>
                            <Mail className="h-4 w-4" />
                          </Button>
                          <Button variant="ghost" size="icon" className="h-8 w-8 opacity-0 group-hover:opacity-100 text-destructive" onClick={() => {
                            setDocumentToDelete(doc.id);
                            setIsDeleteDialogOpen(true);
                          }}>
                            <Trash2 className="h-4 w-4" />
                          </Button>
                        </div>
                      </TableCell>
                    </TableRow>
                  ))}
                </TableBody>
              </Table>
            </div>
          )}
        </main>
      </div>

      {/* Dialogs */}
      <AlertDialog open={isDeleteDialogOpen} onOpenChange={setIsDeleteDialogOpen}>
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>Êtes-vous sûr ?</AlertDialogTitle>
            <AlertDialogDescription>
              Cette action est irréversible. Le document et son fichier physique seront définitivement supprimés.
            </AlertDialogDescription>
          </AlertDialogHeader>
          <AlertDialogFooter>
            <AlertDialogCancel>Annuler</AlertDialogCancel>
            <AlertDialogAction onClick={handleDelete} className="bg-destructive text-destructive-foreground hover:bg-destructive/90">
              Supprimer
            </AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>

      <AlertDialog open={isBulkDeleteDialogOpen} onOpenChange={setIsBulkDeleteDialogOpen}>
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>Suppression groupée</AlertDialogTitle>
            <AlertDialogDescription>
              Vous êtes sur le point de supprimer {selectedIds.length} documents. Cette action est irréversible.
            </AlertDialogDescription>
          </AlertDialogHeader>
          <AlertDialogFooter>
            <AlertDialogCancel>Annuler</AlertDialogCancel>
            <AlertDialogAction onClick={handleBulkDelete} className="bg-destructive text-destructive-foreground hover:bg-destructive/90">
              Tout supprimer
            </AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>

      {documentToEmail && (
        <DocumentSendEmailDialog 
          document={documentToEmail} 
          isOpen={isEmailDialogOpen} 
          onOpenChange={setIsEmailDialogOpen} 
        />
      )}
    </div>
  );
}

// Helper to keep imports consistent with components
function Download(props: any) {
  return (
    <svg
      {...props}
      xmlns="http://www.w3.org/2000/svg"
      width="24"
      height="24"
      viewBox="0 0 24 24"
      fill="none"
      stroke="currentColor"
      strokeWidth="2"
      strokeLinecap="round"
      strokeLinejoin="round"
    >
      <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
      <polyline points="7 10 12 15 17 10" />
      <line x1="12" x2="12" y1="15" y2="3" />
    </svg>
  )
}

function Mail(props: any) {
  return (
    <svg
      {...props}
      xmlns="http://www.w3.org/2000/svg"
      width="24"
      height="24"
      viewBox="0 0 24 24"
      fill="none"
      stroke="currentColor"
      strokeWidth="2"
      strokeLinecap="round"
      strokeLinejoin="round"
    >
      <rect width="20" height="16" x="2" y="4" rx="2" />
      <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7" />
    </svg>
  )
}

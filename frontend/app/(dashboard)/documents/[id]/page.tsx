"use client";

import { useEffect, useState, use } from "react";
import {
  ArrowLeft,
  Download,
  Mail,
  Trash2,
  History,
  Edit2,
  Check,
  X,
  Plus,
  Clock,
  Send,
  User,
  ExternalLink,
} from "lucide-react";
import { useDocumentStore } from "@/lib/stores/documents";
import { useClientStore } from "@/lib/stores/clients";
import { DocumentTypeBadge } from "@/components/documents/document-type-badge";
import { DocumentPreview } from "@/components/documents/document-preview";
import { DocumentReuploadDialog } from "@/components/documents/document-reupload-dialog";
import { DocumentSendEmailDialog } from "@/components/documents/document-send-email-dialog";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Badge } from "@/components/ui/badge";
import { Separator } from "@/components/ui/separator";
import { Skeleton } from "@/components/ui/skeleton";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
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
import { formatBytes, formatDate } from "@/lib/utils";
import Link from "next/link";
import { useRouter } from "next/navigation";
import { toast } from "sonner";

export default function DocumentDetailPage({
  params,
}: {
  params: Promise<{ id: string }>;
}) {
  const { id } = use(params);
  const router = useRouter();
  const {
    currentDocument,
    isLoading,
    fetchDocument,
    updateDocument,
    deleteDocument,
    downloadDocument,
  } = useDocumentStore();
  const { clients, fetchClients } = useClientStore();

  const [isEditingTitle, setIsEditingTitle] = useState(false);
  const [editedTitle, setEditedTitle] = useState("");
  const [isReuploadOpen, setIsReuploadOpen] = useState(false);
  const [isEmailOpen, setIsEmailOpen] = useState(false);
  const [isDeleteDialogOpen, setIsDeleteDialogOpen] = useState(false);
  const [tagInput, setTagInput] = useState("");

  useEffect(() => {
    fetchDocument(id);
    fetchClients();
  }, [id, fetchDocument, fetchClients]);

  useEffect(() => {
    if (currentDocument) {
      setEditedTitle(currentDocument.title);
    }
  }, [currentDocument]);

  const handleUpdateTitle = async () => {
    if (!editedTitle.trim() || editedTitle === currentDocument?.title) {
      setIsEditingTitle(false);
      return;
    }

    try {
      await updateDocument(id, { title: editedTitle });
      toast.success("Titre mis à jour");
      setIsEditingTitle(false);
    } catch (error: any) {
      toast.error(error.message);
    }
  };

  const handleUpdateClient = async (clientId: string) => {
    try {
      await updateDocument(id, {
        client_id: clientId === "none" ? null : clientId,
      });
      toast.success("Client mis à jour");
    } catch (error: any) {
      toast.error(error.message);
    }
  };

  const handleAddTag = async () => {
    if (!tagInput.trim() || currentDocument?.tags.includes(tagInput.trim())) {
      setTagInput("");
      return;
    }

    const newTags = [...(currentDocument?.tags || []), tagInput.trim()];
    try {
      await updateDocument(id, { tags: newTags });
      setTagInput("");
    } catch (error: any) {
      toast.error(error.message);
    }
  };

  const handleRemoveTag = async (tagToRemove: string) => {
    const newTags = (currentDocument?.tags || []).filter(
      (t) => t !== tagToRemove
    );
    try {
      await updateDocument(id, { tags: newTags });
    } catch (error: any) {
      toast.error(error.message);
    }
  };

  const handleDelete = async () => {
    try {
      await deleteDocument(id);
      toast.success("Document supprimé");
      router.push("/documents");
    } catch (error: any) {
      toast.error(error.message);
    }
  };

  if (isLoading && !currentDocument) {
    return (
      <div className="flex h-full flex-col space-y-6 p-8">
        <div className="space-y-2">
          <Skeleton className="h-4 w-24" />
          <Skeleton className="h-10 w-64" />
        </div>
        <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
          <div className="lg:col-span-2 space-y-4">
            <Skeleton className="h-[500px] w-full" />
          </div>
          <div className="space-y-4">
            <Skeleton className="h-48 w-full" />
            <Skeleton className="h-48 w-full" />
          </div>
        </div>
      </div>
    );
  }

  if (!currentDocument) {
    return (
      <div className="flex h-full flex-col items-center justify-center space-y-4 p-8">
        <h2 className="text-xl font-semibold">Document introuvable</h2>
        <Button asChild>
          <Link href="/documents">Retour aux documents</Link>
        </Button>
      </div>
    );
  }

  return (
    <div className="flex h-full flex-col space-y-6 p-8">
      <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div className="space-y-1">
          <Link
            href="/documents"
            className="flex items-center text-sm text-muted-foreground hover:text-foreground mb-2"
          >
            <ArrowLeft className="mr-2 h-4 w-4" />
            Retour à la bibliothèque
          </Link>

          <div className="flex items-center gap-3">
            {isEditingTitle ? (
              <div className="flex items-center gap-2">
                <Input
                  value={editedTitle}
                  onChange={(e) => setEditedTitle(e.target.value)}
                  className="h-9 w-[300px] text-xl font-bold"
                  autoFocus
                  onKeyDown={(e) => e.key === "Enter" && handleUpdateTitle()}
                />
                <Button size="icon" variant="ghost" onClick={handleUpdateTitle}>
                  <Check className="h-4 w-4 text-green-600" />
                </Button>
                <Button
                  size="icon"
                  variant="ghost"
                  onClick={() => {
                    setEditedTitle(currentDocument.title);
                    setIsEditingTitle(false);
                  }}
                >
                  <X className="h-4 w-4 text-red-600" />
                </Button>
              </div>
            ) : (
              <h1 className="group flex items-center gap-2 text-3xl font-bold tracking-tight">
                {currentDocument.title}
                <Button
                  variant="ghost"
                  size="icon"
                  className="h-8 w-8 opacity-0 group-hover:opacity-100 transition-opacity"
                  onClick={() => setIsEditingTitle(true)}
                >
                  <Edit2 className="h-4 w-4" />
                </Button>
              </h1>
            )}
            <DocumentTypeBadge
              type={currentDocument.document_type}
              scriptLanguage={currentDocument.script_language}
              className="mt-1"
            />
          </div>
        </div>

        <div className="flex flex-wrap items-center gap-2">
          <Button
            variant="outline"
            className="gap-2"
            onClick={() => downloadDocument(id)}
          >
            <Download className="h-4 w-4" />
            Télécharger
          </Button>
          <Button
            variant="outline"
            className="gap-2"
            onClick={() => setIsReuploadOpen(true)}
          >
            <History className="h-4 w-4" />
            Mettre à jour
          </Button>
          <Button
            variant="outline"
            className="gap-2"
            onClick={() => setIsEmailOpen(true)}
          >
            <Mail className="h-4 w-4" />
            Envoyer par email
          </Button>
          <Button
            variant="outline"
            className="gap-2 text-destructive hover:bg-destructive/10 hover:text-destructive"
            onClick={() => setIsDeleteDialogOpen(true)}
          >
            <Trash2 className="h-4 w-4" />
            Supprimer
          </Button>
        </div>
      </div>

      <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div className="lg:col-span-2 space-y-6">
          <div className="space-y-4">
            <h3 className="text-lg font-semibold">Prévisualisation</h3>
            <DocumentPreview document={currentDocument} />
          </div>
        </div>

        <div className="space-y-6">
          <div className="rounded-xl border bg-card p-6 shadow-sm space-y-4">
            <h3 className="font-semibold flex items-center gap-2">
              <Clock className="h-4 w-4 text-muted-foreground" />
              Informations
            </h3>
            <Separator />
            <div className="space-y-3">
              <div className="flex justify-between text-sm">
                <span className="text-muted-foreground">Nom original</span>
                <span
                  className="font-medium truncate max-w-[150px]"
                  title={currentDocument.original_filename}
                >
                  {currentDocument.original_filename}
                </span>
              </div>
              <div className="flex justify-between text-sm">
                <span className="text-muted-foreground">Taille</span>
                <span className="font-medium">
                  {formatBytes(currentDocument.file_size)}
                </span>
              </div>
              <div className="flex justify-between text-sm">
                <span className="text-muted-foreground">Version</span>
                <Badge variant="secondary" className="h-5">
                  v{currentDocument.version}
                </Badge>
              </div>
              <div className="flex justify-between text-sm">
                <span className="text-muted-foreground">Importé le</span>
                <span className="font-medium">
                  {formatDate(currentDocument.created_at)}
                </span>
              </div>
            </div>
          </div>

          <div className="rounded-xl border bg-card p-6 shadow-sm space-y-4">
            <h3 className="font-semibold flex items-center gap-2">
              <User className="h-4 w-4 text-muted-foreground" />
              Association
            </h3>
            <Separator />
            <div className="space-y-4">
              <div className="space-y-2">
                <label className="text-xs text-muted-foreground ml-1">
                  Client associé
                </label>
                <Select
                  value={currentDocument.client_id || "none"}
                  onValueChange={handleUpdateClient}
                >
                  <SelectTrigger>
                    <SelectValue placeholder="Aucun client" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="none">Aucun client</SelectItem>
                    {clients.map((client) => (
                      <SelectItem key={client.id} value={client.id}>
                        {client.name}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              </div>

              {currentDocument.client && (
                <Button
                  variant="ghost"
                  size="sm"
                  asChild
                  className="w-full justify-start text-xs"
                >
                  <Link href={`/clients/${currentDocument.client_id}`}>
                    <ExternalLink className="mr-2 h-3 w-3" />
                    Voir la fiche client
                  </Link>
                </Button>
              )}
            </div>
          </div>

          <div className="rounded-xl border bg-card p-6 shadow-sm space-y-4">
            <h3 className="font-semibold flex items-center gap-2">
              <Plus className="h-4 w-4 text-muted-foreground" />
              Tags
            </h3>
            <Separator />
            <div className="space-y-4">
              <div className="flex flex-wrap gap-1.5">
                {currentDocument.tags.length > 0 ? (
                  currentDocument.tags.map((tag) => (
                    <Badge
                      key={tag}
                      variant="outline"
                      className="gap-1 px-2 py-0.5"
                    >
                      {tag}
                      <button
                        onClick={() => handleRemoveTag(tag)}
                        className="rounded-full hover:bg-muted/80 p-0.5"
                      >
                        <X className="h-2 w-2" />
                      </button>
                    </Badge>
                  ))
                ) : (
                  <p className="text-xs text-muted-foreground italic">
                    Aucun tag
                  </p>
                )}
              </div>

              <div className="flex gap-2">
                <Input
                  placeholder="Nouveau tag..."
                  value={tagInput}
                  onChange={(e) => setTagInput(e.target.value)}
                  className="h-8 text-xs"
                  onKeyDown={(e) => e.key === "Enter" && handleAddTag()}
                />
                <Button
                  size="icon"
                  variant="outline"
                  className="h-8 w-8"
                  onClick={handleAddTag}
                >
                  <Plus className="h-3.5 w-3.5" />
                </Button>
              </div>
            </div>
          </div>

          {currentDocument.last_sent_at && (
            <div className="rounded-xl border bg-green-50 dark:bg-green-900/10 p-6 shadow-sm space-y-3">
              <h3 className="font-semibold flex items-center gap-2 text-green-800 dark:text-green-400">
                <Send className="h-4 w-4" />
                Dernier envoi
              </h3>
              <div className="space-y-1 text-sm">
                <p className="text-green-700 dark:text-green-500">
                  Envoyé à :{" "}
                  <span className="font-medium">
                    {currentDocument.last_sent_to}
                  </span>
                </p>
                <p className="text-xs text-green-600/80 dark:text-green-600">
                  Le {formatDate(currentDocument.last_sent_at)}
                </p>
              </div>
            </div>
          )}
        </div>
      </div>

      {/* Dialogs */}
      <DocumentReuploadDialog
        document={currentDocument}
        isOpen={isReuploadOpen}
        onOpenChange={setIsReuploadOpen}
      />

      <DocumentSendEmailDialog
        document={currentDocument}
        isOpen={isEmailOpen}
        onOpenChange={setIsEmailOpen}
      />

      <AlertDialog
        open={isDeleteDialogOpen}
        onOpenChange={setIsDeleteDialogOpen}
      >
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>Supprimer le document</AlertDialogTitle>
            <AlertDialogDescription>
              Êtes-vous sûr de vouloir supprimer ce document ? Cette action est
              irréversible et supprimera également le fichier sur le serveur.
            </AlertDialogDescription>
          </AlertDialogHeader>
          <AlertDialogFooter>
            <AlertDialogCancel>Annuler</AlertDialogCancel>
            <AlertDialogAction
              onClick={handleDelete}
              className="bg-destructive text-destructive-foreground hover:bg-destructive/90"
            >
              Supprimer
            </AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>
    </div>
  );
}

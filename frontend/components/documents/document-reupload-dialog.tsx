"use client";

import { useState, useRef } from "react";
import { 
  Upload, 
  File, 
  X, 
  Loader2, 
  AlertTriangle 
} from "lucide-react";
import { 
  Dialog, 
  DialogContent, 
  DialogHeader, 
  DialogTitle, 
  DialogFooter,
  DialogDescription
} from "@/components/ui/dialog";
import { Button } from "@/components/ui/button";
import { useDocumentStore, type Document } from "@/lib/stores/documents";
import { toast } from "sonner";
import { formatBytes } from "@/lib/utils";

interface DocumentReuploadDialogProps {
  document: Document;
  isOpen: boolean;
  onOpenChange: (open: boolean) => void;
  onSuccess?: (document: Document) => void;
}

export function DocumentReuploadDialog({
  document: currentDocument,
  isOpen,
  onOpenChange,
  onSuccess,
}: DocumentReuploadDialogProps) {
  const { reuploadDocument } = useDocumentStore();
  
  const [file, setFile] = useState<File | null>(null);
  const [isUploading, setIsUploading] = useState(false);
  
  const fileInputRef = useRef<HTMLInputElement>(null);

  const resetForm = () => {
    setFile(null);
    setIsUploading(false);
  };

  const handleFileChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    if (e.target.files && e.target.files[0]) {
      setFile(e.target.files[0]);
    }
  };

  const handleUpload = async () => {
    if (!file) return;

    setIsUploading(true);
    try {
      const updatedDoc = await reuploadDocument(currentDocument.id, file);
      toast.success("Nouvelle version importée avec succès");
      onOpenChange(false);
      resetForm();
      onSuccess?.(updatedDoc);
    } catch (error: any) {
      toast.error(error.message || "Erreur lors de l'importation");
    } finally {
      setIsUploading(false);
    }
  };

  return (
    <Dialog open={isOpen} onOpenChange={(open) => {
      onOpenChange(open);
      if (!open) resetForm();
    }}>
      <DialogContent className="sm:max-w-[450px]">
        <DialogHeader>
          <DialogTitle>Nouvelle version</DialogTitle>
          <DialogDescription>
            Remplacer le fichier actuel par une nouvelle version.
          </DialogDescription>
        </DialogHeader>

        <div className="space-y-4 py-4">
          <div className="flex items-center gap-3 rounded-lg bg-yellow-50 dark:bg-yellow-900/20 p-4 text-yellow-800 dark:text-yellow-400">
            <AlertTriangle className="h-5 w-5 shrink-0" />
            <p className="text-xs">
              Cette action va écraser le fichier actuel. La version passera de 
              <strong> v{currentDocument.version}</strong> à 
              <strong> v{currentDocument.version + 1}</strong>.
            </p>
          </div>

          <div 
            className={`relative flex flex-col items-center justify-center rounded-lg border-2 border-dashed p-8 transition-colors ${
              file ? "border-primary bg-primary/5" : "border-muted-foreground/25 hover:border-primary/50"
            }`}
            onDragOver={(e) => e.preventDefault()}
            onDrop={(e) => {
              e.preventDefault();
              if (e.dataTransfer.files && e.dataTransfer.files[0]) {
                setFile(e.dataTransfer.files[0]);
              }
            }}
          >
            <input 
              type="file" 
              ref={fileInputRef}
              onChange={handleFileChange}
              className="hidden"
            />
            
            {file ? (
              <div className="flex w-full items-center justify-between gap-4">
                <div className="flex items-center gap-3">
                  <div className="rounded-md bg-primary/10 p-2">
                    <File className="h-6 w-6 text-primary" />
                  </div>
                  <div>
                    <p className="text-sm font-medium line-clamp-1">{file.name}</p>
                    <p className="text-xs text-muted-foreground">{formatBytes(file.size)}</p>
                  </div>
                </div>
                <Button 
                  variant="ghost" 
                  size="icon" 
                  className="h-8 w-8 text-muted-foreground"
                  onClick={() => setFile(null)}
                >
                  <X className="h-4 w-4" />
                </Button>
              </div>
            ) : (
              <div className="flex flex-col items-center gap-2 text-center">
                <div className="rounded-full bg-muted p-3">
                  <Upload className="h-6 w-6 text-muted-foreground" />
                </div>
                <div>
                  <Button 
                    variant="link" 
                    className="p-0 h-auto"
                    onClick={() => fileInputRef.current?.click()}
                  >
                    Cliquez pour choisir un fichier
                  </Button>
                  <p className="text-xs text-muted-foreground">ou glissez-déposez ici</p>
                </div>
              </div>
            )}
          </div>
        </div>

        <DialogFooter>
          <Button variant="outline" onClick={() => onOpenChange(false)} disabled={isUploading}>
            Annuler
          </Button>
          <Button onClick={handleUpload} disabled={!file || isUploading}>
            {isUploading ? (
              <>
                <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                Importation...
              </>
            ) : (
              "Mettre à jour"
            )}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

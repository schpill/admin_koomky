"use client";

import { useState, useEffect } from "react";
import { Mail, Loader2, AlertCircle, Send } from "lucide-react";
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogFooter,
  DialogDescription,
} from "@/components/ui/dialog";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { useDocumentStore, type Document } from "@/lib/stores/documents";
import { toast } from "sonner";

interface DocumentSendEmailDialogProps {
  document: Document;
  isOpen: boolean;
  onOpenChange: (open: boolean) => void;
}

export function DocumentSendEmailDialog({
  document,
  isOpen,
  onOpenChange,
}: DocumentSendEmailDialogProps) {
  const { sendEmail } = useDocumentStore();

  const [email, setEmail] = useState("");
  const [message, setMessage] = useState("");
  const [isSending, setIsSending] = useState(false);

  useEffect(() => {
    if (isOpen) {
      if (document.client?.email) {
        setEmail(document.client.email);
      } else if (document.last_sent_to) {
        setEmail(document.last_sent_to);
      } else {
        setEmail("");
      }
      setMessage("");
    }
  }, [isOpen, document]);

  const handleSend = async () => {
    if (!email) return;

    setIsSending(true);
    try {
      await sendEmail(document.id, { email, message });
      toast.success("E-mail envoyé avec succès");
      onOpenChange(false);
    } catch (error: any) {
      toast.error(error.message || "Erreur lors de l'envoi de l'e-mail");
    } finally {
      setIsSending(false);
    }
  };

  const isLargeFile = document.file_size > 10 * 1024 * 1024; // > 10 MB

  return (
    <Dialog open={isOpen} onOpenChange={onOpenChange}>
      <DialogContent className="sm:max-w-[450px]">
        <DialogHeader>
          <DialogTitle className="flex items-center gap-2">
            <Mail className="h-5 w-5" />
            Envoyer par email
          </DialogTitle>
          <DialogDescription>
            Envoyer &quot;{document.title}&quot; en tant que pièce jointe.
          </DialogDescription>
        </DialogHeader>

        <div className="space-y-4 py-4">
          {isLargeFile && (
            <div className="flex items-start gap-3 rounded-lg bg-blue-50 dark:bg-blue-900/20 p-3 text-blue-800 dark:text-blue-400">
              <AlertCircle className="h-5 w-5 shrink-0 mt-0.5" />
              <p className="text-xs">
                Ce fichier est volumineux (
                {Math.round(document.file_size / 1024 / 1024)} MB). Certains
                serveurs de messagerie pourraient le rejeter.
              </p>
            </div>
          )}

          <div className="grid gap-2">
            <Label htmlFor="email">Destinataire</Label>
            <Input
              id="email"
              type="email"
              placeholder="adresse@exemple.com"
              value={email}
              onChange={(e) => setEmail(e.target.value)}
              autoFocus
            />
          </div>

          <div className="grid gap-2">
            <Label htmlFor="message">Message (optionnel)</Label>
            <Textarea
              id="message"
              placeholder="Ajoutez un message personnel..."
              value={message}
              onChange={(e) => setMessage(e.target.value)}
              rows={4}
            />
          </div>
        </div>

        <DialogFooter>
          <Button
            variant="outline"
            onClick={() => onOpenChange(false)}
            disabled={isSending}
          >
            Annuler
          </Button>
          <Button onClick={handleSend} disabled={!email || isSending}>
            {isSending ? (
              <>
                <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                Envoi en cours...
              </>
            ) : (
              <>
                <Send className="mr-2 h-4 w-4" />
                Envoyer
              </>
            )}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

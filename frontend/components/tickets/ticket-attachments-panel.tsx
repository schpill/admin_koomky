"use client";
import { useRef } from "react";
import { Button } from "@/components/ui/button";
import { Download, Paperclip, Trash2, Upload } from "lucide-react";
import { useI18n } from "@/components/providers/i18n-provider";

interface AttachmentsPanelProps {
  ticketId: string;
  documents: any[];
  onDetach: (docId: string) => void;
  onUpload: (formData: FormData) => void;
  onAttach: (documentId: string) => void;
}

function formatBytes(bytes: number): string {
  if (!bytes) return "\u2014";
  if (bytes < 1024) return `${bytes} B`;
  if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`;
  return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
}

export function TicketAttachmentsPanel({
  ticketId,
  documents,
  onDetach,
  onUpload,
  onAttach,
}: AttachmentsPanelProps) {
  const { t } = useI18n();
  const fileInputRef = useRef<HTMLInputElement>(null);

  const handleFileChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0];
    if (!file) return;
    const fd = new FormData();
    fd.append("file", file);
    onUpload(fd);
    e.target.value = "";
  };

  return (
    <div className="space-y-4">
      <div className="flex gap-2">
        <Button
          variant="outline"
          size="sm"
          onClick={() => fileInputRef.current?.click()}
        >
          <Upload className="mr-2 h-4 w-4" />
          {t("tickets.attachments.upload")}
        </Button>
        <input
          ref={fileInputRef}
          type="file"
          className="hidden"
          onChange={handleFileChange}
          aria-label="Upload file"
        />
      </div>

      {documents.length === 0 ? (
        <p className="text-sm text-muted-foreground">{t("tickets.attachments.noAttachments")}</p>
      ) : (
        <div className="space-y-2">
          {documents.map((doc: any) => (
            <div
              key={doc.id}
              className="flex items-center justify-between rounded-lg border p-3"
            >
              <div className="flex items-center gap-3">
                <Paperclip className="h-4 w-4 text-muted-foreground" />
                <div>
                  <p className="text-sm font-medium">
                    {doc.title ?? doc.original_filename ?? doc.id}
                  </p>
                  <p className="text-xs text-muted-foreground">
                    {formatBytes(doc.file_size)}
                  </p>
                </div>
              </div>
              <div className="flex gap-1">
                <Button variant="ghost" size="sm" asChild aria-label="Download">
                  <a href={`/api/v1/documents/${doc.id}/download`} download>
                    <Download className="h-3.5 w-3.5" />
                  </a>
                </Button>
                <Button
                  variant="ghost"
                  size="sm"
                  onClick={() => onDetach(doc.id)}
                  aria-label="Detach"
                >
                  <Trash2 className="h-3.5 w-3.5" />
                </Button>
              </div>
            </div>
          ))}
        </div>
      )}
    </div>
  );
}

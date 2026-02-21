"use client";

import {
  Download,
  Mail,
  Trash2,
  MoreVertical,
  ExternalLink,
  History,
} from "lucide-react";
import { formatBytes, formatDate } from "@/lib/utils";
import {
  Card,
  CardContent,
  CardFooter,
  CardHeader,
} from "@/components/ui/card";
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
  DropdownMenuSeparator,
} from "@/components/ui/dropdown-menu";
import { Button } from "@/components/ui/button";
import { Checkbox } from "@/components/ui/checkbox";
import { Badge } from "@/components/ui/badge";
import { DocumentTypeBadge } from "./document-type-badge";
import type { Document } from "@/lib/stores/documents";
import Link from "next/link";

interface DocumentCardProps {
  document: Document;
  isSelected?: boolean;
  onSelect?: (selected: boolean) => void;
  onDownload?: () => void;
  onSendEmail?: () => void;
  onDelete?: () => void;
}

export function DocumentCard({
  document,
  isSelected,
  onSelect,
  onDownload,
  onSendEmail,
  onDelete,
}: DocumentCardProps) {
  return (
    <Card className="group relative overflow-hidden transition-all hover:shadow-md">
      <CardHeader className="p-4 pb-0">
        <div className="flex items-start justify-between">
          <div className="flex items-center gap-2">
            <Checkbox
              checked={isSelected}
              onCheckedChange={(checked) => onSelect?.(checked as boolean)}
              className="z-10"
            />
            <DocumentTypeBadge
              type={document.document_type}
              scriptLanguage={document.script_language}
              showLabel={false}
            />
          </div>

          <DropdownMenu>
            <DropdownMenuTrigger asChild>
              <Button variant="ghost" size="icon" className="h-8 w-8">
                <MoreVertical className="h-4 w-4" />
              </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end">
              <DropdownMenuItem asChild>
                <Link
                  href={`/documents/${document.id}`}
                  className="flex w-full items-center"
                >
                  <ExternalLink className="mr-2 h-4 w-4" />
                  Ouvrir
                </Link>
              </DropdownMenuItem>
              <DropdownMenuItem onClick={onDownload}>
                <Download className="mr-2 h-4 w-4" />
                Télécharger
              </DropdownMenuItem>
              <DropdownMenuItem onClick={onSendEmail}>
                <Mail className="mr-2 h-4 w-4" />
                Envoyer par email
              </DropdownMenuItem>
              <DropdownMenuSeparator />
              <DropdownMenuItem
                onClick={onDelete}
                className="text-red-600 focus:text-red-600"
              >
                <Trash2 className="mr-2 h-4 w-4" />
                Supprimer
              </DropdownMenuItem>
            </DropdownMenuContent>
          </DropdownMenu>
        </div>
      </CardHeader>

      <CardContent className="p-4 pt-2">
        <Link href={`/documents/${document.id}`}>
          <h3
            className="line-clamp-1 font-semibold text-sm hover:underline cursor-pointer"
            title={document.title}
          >
            {document.title}
          </h3>
        </Link>
        <p className="mt-1 text-xs text-muted-foreground line-clamp-1">
          {document.original_filename}
        </p>

        {document.client && (
          <div className="mt-2">
            <Badge variant="outline" className="text-[10px] font-normal">
              {document.client.name}
            </Badge>
          </div>
        )}
      </CardContent>

      <CardFooter className="flex items-center justify-between p-4 pt-0 text-[10px] text-muted-foreground">
        <div className="flex items-center gap-2">
          <span>{formatBytes(document.file_size)}</span>
          <span>•</span>
          <span>{formatDate(document.created_at)}</span>
        </div>

        {document.version > 1 && (
          <Badge
            variant="secondary"
            className="h-4 px-1 text-[9px] font-normal gap-0.5"
          >
            <History className="h-2 w-2" />v{document.version}
          </Badge>
        )}
      </CardFooter>
    </Card>
  );
}

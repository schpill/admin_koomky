"use client";

import { useState } from "react";
import { useRouter } from "next/navigation";
import {
  LayoutTemplate,
  MoreVertical,
  Edit,
  Copy,
  Trash2,
  Play,
} from "lucide-react";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";
import { Badge } from "@/components/ui/badge";
import { useProjectTemplatesStore } from "@/lib/stores/project-templates";
import { toast } from "sonner";

interface ProjectTemplateCardProps {
  template: {
    id: string;
    name: string;
    description: string | null;
    billing_type: string | null;
    tasks_count: number;
  };
  onInstantiate?: (templateId: string) => void;
}

export function ProjectTemplateCard({
  template,
  onInstantiate,
}: ProjectTemplateCardProps) {
  const router = useRouter();
  const { deleteTemplate, duplicateTemplate } = useProjectTemplatesStore();
  const [isDeleting, setIsDeleting] = useState(false);
  const [isDuplicating, setIsDuplicating] = useState(false);

  const handleDelete = async () => {
    setIsDeleting(true);
    try {
      await deleteTemplate(template.id);
      toast.success("Template supprimé");
    } catch (error) {
      toast.error("Erreur lors de la suppression");
    } finally {
      setIsDeleting(false);
    }
  };

  const handleDuplicate = async () => {
    setIsDuplicating(true);
    try {
      await duplicateTemplate(template.id);
      toast.success("Template duplicué");
    } catch (error) {
      toast.error("Erreur lors de la duplication");
    } finally {
      setIsDuplicating(false);
    }
  };

  return (
    <Card className="group relative">
      <CardHeader className="pb-3">
        <div className="flex items-start justify-between">
          <div className="flex items-center gap-2">
            <LayoutTemplate className="h-5 w-5 text-muted-foreground" />
            <CardTitle className="text-lg">
              <button
                type="button"
                className="text-left hover:underline"
                onClick={() =>
                  router.push(`/settings/project-templates/${template.id}`)
                }
              >
                {template.name}
              </button>
            </CardTitle>
          </div>
          <DropdownMenu>
            <DropdownMenuTrigger asChild>
              <Button
                variant="ghost"
                size="icon"
                className="h-8 w-8"
                aria-label="Actions du template"
              >
                <MoreVertical className="h-4 w-4" />
              </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" forceMount>
              <DropdownMenuItem
                onClick={() =>
                  router.push(`/settings/project-templates/${template.id}`)
                }
              >
                <Edit className="mr-2 h-4 w-4" />
                Modifier
              </DropdownMenuItem>
              <DropdownMenuItem
                onClick={handleDuplicate}
                disabled={isDuplicating}
              >
                <Copy className="mr-2 h-4 w-4" />
                {isDuplicating ? "Duplication..." : "Dupliquer"}
              </DropdownMenuItem>
              <DropdownMenuItem
                onClick={handleDelete}
                className="text-red-600"
                disabled={isDeleting}
              >
                <Trash2 className="mr-2 h-4 w-4" />
                {isDeleting ? "Suppression..." : "Supprimer"}
              </DropdownMenuItem>
            </DropdownMenuContent>
          </DropdownMenu>
        </div>
      </CardHeader>
      <CardContent>
        {template.description && (
          <p className="mb-3 text-sm text-muted-foreground line-clamp-2">
            {template.description}
          </p>
        )}

        <div className="flex items-center justify-between">
          <div className="flex items-center gap-2">
            {template.billing_type && (
              <Badge variant="outline">
                {template.billing_type === "hourly" ? "Horaire" : "Forfait"}
              </Badge>
            )}
            <span className="text-sm text-muted-foreground">
              {template.tasks_count} tâche
              {template.tasks_count !== 1 ? "s" : ""}
            </span>
          </div>

          <Button
            variant="outline"
            size="sm"
            onClick={() => onInstantiate?.(template.id)}
          >
            <Play className="mr-2 h-4 w-4" />
            Utiliser
          </Button>
        </div>
      </CardContent>
    </Card>
  );
}

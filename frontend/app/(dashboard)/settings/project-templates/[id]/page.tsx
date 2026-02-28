"use client";

import { useEffect, useState } from "react";
import { useRouter, useParams } from "next/navigation";
import { ArrowLeft, Copy, Pencil, Trash2, Play } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Skeleton } from "@/components/ui/skeleton";
import { useProjectTemplatesStore } from "@/lib/stores/project-templates";
import { toast } from "sonner";
import { InstantiateTemplateDialog } from "@/components/project-templates/instantiate-template-dialog";
import {
  ProjectTemplateForm,
  type ProjectTemplateFormValues,
} from "@/components/project-templates/project-template-form";

export default function ProjectTemplateDetailPage() {
  const router = useRouter();
  const params = useParams();
  const templateId = params.id as string;
  const {
    templates,
    fetchTemplates,
    deleteTemplate,
    duplicateTemplate,
    updateTemplate,
    isLoading,
  } = useProjectTemplatesStore();
  const [showInstantiate, setShowInstantiate] = useState(false);
  const [isDeleting, setIsDeleting] = useState(false);
  const [isDuplicating, setIsDuplicating] = useState(false);
  const [isEditing, setIsEditing] = useState(false);

  useEffect(() => {
    fetchTemplates();
  }, [fetchTemplates]);

  const template = templates.find((t) => t.id === templateId);

  const handleDelete = async () => {
    if (!confirm("Êtes-vous sûr de vouloir supprimer ce template ?")) return;

    setIsDeleting(true);
    try {
      await deleteTemplate(templateId);
      toast.success("Template supprimé");
      router.push("/settings/project-templates");
    } catch (error) {
      toast.error("Erreur lors de la suppression");
    } finally {
      setIsDeleting(false);
    }
  };

  const handleDuplicate = async () => {
    setIsDuplicating(true);
    try {
      const newTemplate = await duplicateTemplate(templateId);
      toast.success("Template duplicué");
      router.push(`/settings/project-templates/${newTemplate.id}`);
    } catch (error) {
      toast.error("Erreur lors de la duplication");
    } finally {
      setIsDuplicating(false);
    }
  };

  const handleUpdate = async (values: ProjectTemplateFormValues) => {
    try {
      await updateTemplate(templateId, {
        ...values,
        tasks: values.tasks
          .filter((task) => task.title.trim() !== "")
          .map(({ id, ...task }) => ({
            ...task,
            description: task.description || null,
          })),
      });
      toast.success("Template mis à jour");
      setIsEditing(false);
    } catch (error) {
      toast.error("Erreur lors de la mise à jour");
    }
  };

  if (isLoading && templates.length === 0) {
    return (
      <div className="container mx-auto max-w-4xl p-6">
        <Skeleton className="h-8 w-32 mb-6" />
        <Skeleton className="h-64 w-full" />
      </div>
    );
  }

  if (!template) {
    return (
      <div className="container mx-auto max-w-4xl p-6">
        <Button variant="ghost" onClick={() => router.back()}>
          <ArrowLeft className="mr-2 h-4 w-4" />
          Retour
        </Button>
        <p className="mt-4 text-muted-foreground">Template non trouvé</p>
      </div>
    );
  }

  return (
    <div className="container mx-auto max-w-4xl p-6">
      <Button variant="ghost" className="mb-4" onClick={() => router.back()}>
        <ArrowLeft className="mr-2 h-4 w-4" />
        Retour aux templates
      </Button>

      <div className="flex items-start justify-between mb-6">
        <div>
          <h1 className="text-3xl font-bold">{template.name}</h1>
          {template.description && (
            <p className="mt-2 text-muted-foreground">{template.description}</p>
          )}
        </div>
        <div className="flex gap-2">
          <Button
            variant="outline"
            onClick={() => setIsEditing((value) => !value)}
          >
            <Pencil className="mr-2 h-4 w-4" />
            {isEditing ? "Annuler l'édition" : "Modifier"}
          </Button>
          <Button
            variant="outline"
            onClick={handleDuplicate}
            disabled={isDuplicating}
          >
            <Copy className="mr-2 h-4 w-4" />
            {isDuplicating ? "Duplication..." : "Dupliquer"}
          </Button>
          <Button variant="default" onClick={() => setShowInstantiate(true)}>
            <Play className="mr-2 h-4 w-4" />
            Utiliser
          </Button>
          <Button
            variant="destructive"
            onClick={handleDelete}
            disabled={isDeleting}
          >
            <Trash2 className="mr-2 h-4 w-4" />
            {isDeleting ? "Suppression..." : "Supprimer"}
          </Button>
        </div>
      </div>

      {isEditing ? (
        <Card>
          <CardHeader>
            <CardTitle>Modifier le template</CardTitle>
          </CardHeader>
          <CardContent>
            <ProjectTemplateForm
              submitLabel="Enregistrer les modifications"
              onSubmit={handleUpdate}
              defaultValues={{
                name: template.name,
                description: template.description || "",
                billing_type:
                  template.billing_type === "hourly" ||
                  template.billing_type === "fixed"
                    ? template.billing_type
                    : null,
                default_hourly_rate: template.default_hourly_rate,
                default_currency: template.default_currency || "EUR",
                estimated_hours: template.estimated_hours,
                tasks: (template.tasks || []).map((task) => ({
                  id: task.id,
                  title: task.title,
                  description: task.description || "",
                  estimated_hours: task.estimated_hours,
                  priority:
                    task.priority === "low" ||
                    task.priority === "high" ||
                    task.priority === "urgent"
                      ? task.priority
                      : "medium",
                  sort_order: task.sort_order,
                })),
              }}
            />
          </CardContent>
        </Card>
      ) : (
        <div className="grid gap-6 md:grid-cols-2">
          <Card>
            <CardHeader>
              <CardTitle>Informations</CardTitle>
            </CardHeader>
            <CardContent className="space-y-3">
              <div className="flex justify-between">
                <span className="text-muted-foreground">
                  Type de facturation
                </span>
                <Badge variant="outline">
                  {template.billing_type === "hourly"
                    ? "Horaire"
                    : template.billing_type === "fixed"
                      ? "Forfait"
                      : "—"}
                </Badge>
              </div>
              {template.default_hourly_rate && (
                <div className="flex justify-between">
                  <span className="text-muted-foreground">Taux horaire</span>
                  <span>{template.default_hourly_rate} €</span>
                </div>
              )}
              {template.estimated_hours && (
                <div className="flex justify-between">
                  <span className="text-muted-foreground">Heures estimées</span>
                  <span>{template.estimated_hours}h</span>
                </div>
              )}
              <div className="flex justify-between">
                <span className="text-muted-foreground">Nombre de tâches</span>
                <span>{template.tasks?.length || 0}</span>
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle>Tâches</CardTitle>
            </CardHeader>
            <CardContent>
              {template.tasks && template.tasks.length > 0 ? (
                <ul className="space-y-3">
                  {template.tasks.map((task, index) => (
                    <li key={task.id} className="flex items-start gap-3">
                      <span className="flex h-6 w-6 items-center justify-center rounded-full bg-muted text-xs">
                        {index + 1}
                      </span>
                      <div className="flex-1">
                        <p className="font-medium">{task.title}</p>
                        {task.description && (
                          <p className="text-sm text-muted-foreground line-clamp-1">
                            {task.description}
                          </p>
                        )}
                        <div className="mt-1 flex gap-2">
                          <Badge variant="secondary" className="text-xs">
                            {task.priority}
                          </Badge>
                          {task.estimated_hours && (
                            <span className="text-xs text-muted-foreground">
                              {task.estimated_hours}h
                            </span>
                          )}
                        </div>
                      </div>
                    </li>
                  ))}
                </ul>
              ) : (
                <p className="text-muted-foreground text-sm">Aucune tâche</p>
              )}
            </CardContent>
          </Card>
        </div>
      )}

      <InstantiateTemplateDialog
        templateId={templateId}
        open={showInstantiate}
        onOpenChange={setShowInstantiate}
        onSuccess={(projectId) => {
          setShowInstantiate(false);
          router.push(`/projects/${projectId}`);
        }}
      />
    </div>
  );
}

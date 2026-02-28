"use client";

import { useEffect, useState } from "react";
import { useRouter } from "next/navigation";
import { Plus, LayoutTemplate } from "lucide-react";
import { Button } from "@/components/ui/button";
import { useProjectTemplatesStore } from "@/lib/stores/project-templates";
import { ProjectTemplateCard } from "@/components/project-templates/project-template-card";
import { Skeleton } from "@/components/ui/skeleton";
import { InstantiateTemplateDialog } from "@/components/project-templates/instantiate-template-dialog";

export default function ProjectTemplatesPage() {
  const router = useRouter();
  const { templates, isLoading, fetchTemplates } = useProjectTemplatesStore();
  const templateItems = Array.isArray(templates) ? templates : [];
  const [instantiateTemplateId, setInstantiateTemplateId] = useState<
    string | null
  >(null);

  useEffect(() => {
    fetchTemplates();
  }, [fetchTemplates]);

  return (
    <div className="container mx-auto max-w-6xl p-6">
      <div className="mb-6 flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold">Templates de projets</h1>
          <p className="text-muted-foreground">
            Créez et gérez des modèles de projets réutilisables
          </p>
        </div>
        <Button onClick={() => router.push("/settings/project-templates/new")}>
          <Plus className="mr-2 h-4 w-4" />
          Nouveau template
        </Button>
      </div>

      {isLoading ? (
        <div className="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
          {[1, 2, 3].map((i) => (
            <Skeleton key={i} className="h-48 w-full" />
          ))}
        </div>
      ) : templateItems.length === 0 ? (
        <div className="flex flex-col items-center justify-center py-12 text-center">
          <LayoutTemplate className="mb-4 h-12 w-12 text-muted-foreground" />
          <h3 className="mb-2 text-lg font-semibold">Aucun template</h3>
          <p className="mb-4 text-muted-foreground">
            Créez votre premier template de projet pour gagner du temps
          </p>
          <Button
            onClick={() => router.push("/settings/project-templates/new")}
          >
            <Plus className="mr-2 h-4 w-4" />
            Créer un template
          </Button>
        </div>
      ) : (
        <div className="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
          {templateItems.map((template) => (
            <ProjectTemplateCard
              key={template.id}
              template={template}
              onInstantiate={(id) => setInstantiateTemplateId(id)}
            />
          ))}
        </div>
      )}

      <InstantiateTemplateDialog
        templateId={instantiateTemplateId}
        open={!!instantiateTemplateId}
        onOpenChange={(open) => {
          if (!open) setInstantiateTemplateId(null);
        }}
        onSuccess={(projectId) => {
          setInstantiateTemplateId(null);
          router.push(`/projects/${projectId}`);
        }}
      />
    </div>
  );
}

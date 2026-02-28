"use client";

import { useRouter } from "next/navigation";
import { ArrowLeft } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import {
  ProjectTemplateForm,
  type ProjectTemplateFormValues,
} from "@/components/project-templates/project-template-form";
import { useProjectTemplatesStore } from "@/lib/stores/project-templates";
import { toast } from "sonner";

export default function NewProjectTemplatePage() {
  const router = useRouter();
  const { createTemplate } = useProjectTemplatesStore();

  const onSubmit = async (data: ProjectTemplateFormValues) => {
    try {
      const template = await createTemplate({
        ...data,
        tasks: data.tasks
          .filter((task) => task.title.trim() !== "")
          .map(({ id, ...task }) => ({
            ...task,
            description: task.description || null,
          })),
      });

      toast.success("Template créé avec succès");
      router.push(`/settings/project-templates/${template.id}`);
    } catch (error) {
      toast.error("Erreur lors de la création du template");
    }
  };

  return (
    <div className="container mx-auto max-w-3xl p-6">
      <Button variant="ghost" className="mb-4" onClick={() => router.back()}>
        <ArrowLeft className="mr-2 h-4 w-4" />
        Retour
      </Button>

      <h1 className="mb-6 text-3xl font-bold">Nouveau template de projet</h1>

      <Card>
        <CardHeader>
          <CardTitle>Configuration du template</CardTitle>
        </CardHeader>
        <CardContent>
          <ProjectTemplateForm
            submitLabel="Créer le template"
            onSubmit={onSubmit}
            defaultValues={{
              name: "",
              description: "",
              billing_type: "hourly",
              default_hourly_rate: null,
              default_currency: "EUR",
              estimated_hours: null,
              tasks: [],
            }}
          />
        </CardContent>
      </Card>
    </div>
  );
}

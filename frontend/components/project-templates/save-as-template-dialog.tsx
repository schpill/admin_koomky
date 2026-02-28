"use client";

import { zodResolver } from "@hookform/resolvers/zod";
import { useForm } from "react-hook-form";
import { z } from "zod";
import { toast } from "sonner";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { useProjectTemplatesStore } from "@/lib/stores/project-templates";

const saveAsTemplateSchema = z.object({
  name: z.string().min(1, "Le nom du template est requis"),
  description: z.string().optional(),
});

type SaveAsTemplateValues = z.infer<typeof saveAsTemplateSchema>;

interface SaveAsTemplateDialogProps {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  projectId: string;
  projectName: string;
}

export function SaveAsTemplateDialog({
  open,
  onOpenChange,
  projectId,
  projectName,
}: SaveAsTemplateDialogProps) {
  const { saveProjectAsTemplate } = useProjectTemplatesStore();
  const {
    register,
    handleSubmit,
    reset,
    formState: { errors, isSubmitting },
  } = useForm<SaveAsTemplateValues>({
    resolver: zodResolver(saveAsTemplateSchema),
    defaultValues: {
      name: projectName,
      description: "",
    },
  });

  const onSubmit = async (values: SaveAsTemplateValues) => {
    try {
      await saveProjectAsTemplate(
        projectId,
        values.name,
        values.description || ""
      );
      toast.success("Template enregistré");
      onOpenChange(false);
      reset({
        name: projectName,
        description: "",
      });
    } catch (error) {
      toast.error((error as Error).message || "Erreur lors de la sauvegarde");
    }
  };

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Sauvegarder comme template</DialogTitle>
          <DialogDescription>
            Enregistrez ce projet comme base réutilisable pour de futurs
            projets.
          </DialogDescription>
        </DialogHeader>

        <form onSubmit={handleSubmit(onSubmit)} className="space-y-4">
          <div className="space-y-2">
            <Label htmlFor="save-template-name">Nom du template</Label>
            <Input id="save-template-name" {...register("name")} />
            {errors.name && (
              <p className="text-sm text-destructive">{errors.name.message}</p>
            )}
          </div>

          <div className="space-y-2">
            <Label htmlFor="save-template-description">Description</Label>
            <Textarea
              id="save-template-description"
              rows={3}
              {...register("description")}
            />
          </div>

          <DialogFooter>
            <Button
              type="button"
              variant="outline"
              onClick={() => onOpenChange(false)}
            >
              Annuler
            </Button>
            <Button type="submit" disabled={isSubmitting}>
              {isSubmitting ? "Sauvegarde..." : "Sauvegarder le template"}
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
}

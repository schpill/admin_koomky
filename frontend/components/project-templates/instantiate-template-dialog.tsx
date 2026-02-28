"use client";

import { useState, useEffect } from "react";
import { z } from "zod";
import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
  DialogFooter,
} from "@/components/ui/dialog";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { useProjectTemplatesStore } from "@/lib/stores/project-templates";
import { useClientStore } from "@/lib/stores/clients";
import { toast } from "sonner";

const instantiateSchema = z.object({
  name: z.string().min(1, "Le nom est requis").max(255),
  client_id: z.string().min(1, "Le client est requis"),
  start_date: z.string().optional(),
  deadline: z.string().optional(),
});

type InstantiateFormData = z.infer<typeof instantiateSchema>;

interface InstantiateTemplateDialogProps {
  templateId: string | null;
  open: boolean;
  onOpenChange: (open: boolean) => void;
  onSuccess: (projectId: string) => void;
}

export function InstantiateTemplateDialog({
  templateId,
  open,
  onOpenChange,
  onSuccess,
}: InstantiateTemplateDialogProps) {
  const { instantiateTemplate, templates } = useProjectTemplatesStore();
  const { clients, fetchClients } = useClientStore();
  const [isSubmitting, setIsSubmitting] = useState(false);

  const template = templates.find((t) => t.id === templateId);

  const {
    register,
    handleSubmit,
    reset,
    formState: { errors },
  } = useForm<InstantiateFormData>({
    resolver: zodResolver(instantiateSchema),
    defaultValues: {
      name: template?.name || "",
    },
  });

  useEffect(() => {
    if (open) {
      fetchClients();
      reset({
        name: template?.name || "",
      });
    }
  }, [open, fetchClients, reset, template?.id, template?.name]);

  const onSubmit = async (data: InstantiateFormData) => {
    if (!templateId) return;

    setIsSubmitting(true);
    try {
      const result = await instantiateTemplate(templateId, data);
      toast.success("Projet créé avec succès");
      onSuccess(result.id);
    } catch (error) {
      toast.error("Erreur lors de la création du projet");
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="sm:max-w-md">
        <DialogHeader>
          <DialogTitle>Créer un projet depuis le template</DialogTitle>
          <DialogDescription>
            Renseignez le client et les dates pour instancier ce template.
          </DialogDescription>
        </DialogHeader>

        <form onSubmit={handleSubmit(onSubmit)} className="space-y-4">
          <div className="space-y-2">
            <Label htmlFor="name">Nom du projet</Label>
            <Input id="name" {...register("name")} />
            {errors.name && (
              <p className="text-sm text-red-500">{errors.name.message}</p>
            )}
          </div>

          <div className="space-y-2">
            <Label htmlFor="client_id">Client</Label>
            <select
              id="client_id"
              {...register("client_id")}
              className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
            >
              <option value="">Sélectionner un client</option>
              {clients.map((client) => (
                <option key={client.id} value={client.id}>
                  {client.name}
                </option>
              ))}
            </select>
            {errors.client_id && (
              <p className="text-sm text-red-500">{errors.client_id.message}</p>
            )}
          </div>

          {template?.tasks?.length ? (
            <div className="space-y-2">
              <Label>Prévisualisation des tâches</Label>
              <ul className="rounded-md border p-3 text-sm text-muted-foreground">
                {template.tasks.map((task) => (
                  <li key={task.id}>{task.title}</li>
                ))}
              </ul>
            </div>
          ) : null}

          <div className="grid grid-cols-2 gap-4">
            <div className="space-y-2">
              <Label htmlFor="start_date">Date de début</Label>
              <Input id="start_date" type="date" {...register("start_date")} />
            </div>
            <div className="space-y-2">
              <Label htmlFor="deadline">Échéance</Label>
              <Input id="deadline" type="date" {...register("deadline")} />
            </div>
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
              {isSubmitting ? "Création..." : "Créer le projet"}
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
}

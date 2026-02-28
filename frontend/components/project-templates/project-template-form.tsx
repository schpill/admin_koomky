"use client";

import { useEffect } from "react";
import { zodResolver } from "@hookform/resolvers/zod";
import { useForm } from "react-hook-form";
import { z } from "zod";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import {
  ProjectTemplateTaskBuilder,
  type ProjectTemplateTaskInput,
} from "@/components/project-templates/project-template-task-builder";

export const projectTemplateSchema = z.object({
  name: z.string().min(1, "Le nom du template est requis"),
  description: z.string().max(2000).optional(),
  billing_type: z.enum(["hourly", "fixed"]).nullable().optional(),
  default_hourly_rate: z.number().nullable().optional(),
  default_currency: z.string().min(3).max(3).default("EUR"),
  estimated_hours: z.number().nullable().optional(),
  tasks: z.array(
    z.object({
      id: z.string(),
      title: z.string(),
      description: z.string(),
      estimated_hours: z.number().nullable(),
      priority: z.enum(["low", "medium", "high", "urgent"]),
      sort_order: z.number().int(),
    })
  ),
});

export type ProjectTemplateFormValues = z.infer<typeof projectTemplateSchema>;

interface ProjectTemplateFormProps {
  defaultValues: ProjectTemplateFormValues;
  submitLabel: string;
  onSubmit: (values: ProjectTemplateFormValues) => Promise<void> | void;
}

export function ProjectTemplateForm({
  defaultValues,
  submitLabel,
  onSubmit,
}: ProjectTemplateFormProps) {
  const {
    register,
    handleSubmit,
    setValue,
    watch,
    reset,
    formState: { errors, isSubmitting },
  } = useForm<ProjectTemplateFormValues>({
    resolver: zodResolver(projectTemplateSchema),
    defaultValues,
  });

  useEffect(() => {
    reset(defaultValues);
  }, [defaultValues, reset]);

  const billingType = watch("billing_type");
  const tasks = watch("tasks") ?? [];

  return (
    <form onSubmit={handleSubmit((values) => onSubmit(values))} className="space-y-6">
      <div className="space-y-2">
        <Label htmlFor="template-name">Nom du template</Label>
        <Input id="template-name" {...register("name")} />
        {errors.name && (
          <p className="text-sm text-destructive">{errors.name.message}</p>
        )}
      </div>

      <div className="space-y-2">
        <Label htmlFor="template-description">Description</Label>
        <Textarea id="template-description" rows={3} {...register("description")} />
      </div>

      <div className="grid gap-4 md:grid-cols-2">
        <div className="space-y-2">
          <Label>Type de facturation</Label>
          <Select
            value={billingType ?? undefined}
            onValueChange={(value) =>
              setValue("billing_type", value as "hourly" | "fixed")
            }
          >
            <SelectTrigger>
              <SelectValue placeholder="Sélectionner un type" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="hourly">Horaire</SelectItem>
              <SelectItem value="fixed">Forfait</SelectItem>
            </SelectContent>
          </Select>
        </div>

        <div className="space-y-2">
          <Label htmlFor="template-currency">Devise</Label>
          <Input
            id="template-currency"
            maxLength={3}
            {...register("default_currency")}
          />
        </div>

        {billingType === "hourly" && (
          <div className="space-y-2">
            <Label htmlFor="template-hourly-rate">Taux horaire</Label>
            <Input
              id="template-hourly-rate"
              type="number"
              step="0.01"
              {...register("default_hourly_rate", {
                setValueAs: (value) => (value === "" ? null : Number(value)),
              })}
            />
          </div>
        )}

        <div className="space-y-2">
          <Label htmlFor="template-estimated-hours">Heures estimées</Label>
          <Input
            id="template-estimated-hours"
            type="number"
            step="0.25"
            {...register("estimated_hours", {
              setValueAs: (value) => (value === "" ? null : Number(value)),
            })}
          />
        </div>
      </div>

      <ProjectTemplateTaskBuilder
        value={tasks as ProjectTemplateTaskInput[]}
        onChange={(nextTasks) => setValue("tasks", nextTasks, { shouldDirty: true })}
      />

      <div className="flex justify-end">
        <Button type="submit" disabled={isSubmitting}>
          {isSubmitting ? "Enregistrement..." : submitLabel}
        </Button>
      </div>
    </form>
  );
}

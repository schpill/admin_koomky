"use client";
import { useEffect, useState } from "react";
import { useForm } from "react-hook-form";
import {
  Dialog,
  DialogContent,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Textarea } from "@/components/ui/textarea";
import { Label } from "@/components/ui/label";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { apiClient } from "@/lib/api";
import { useI18n } from "@/components/providers/i18n-provider";

type TicketPriority = "low" | "normal" | "high" | "urgent";

interface TicketFormData {
  title: string;
  description: string;
  client_id?: string;
  project_id?: string;
  assigned_to?: string;
  priority: TicketPriority;
  category?: string;
  tags?: string;
  deadline?: string;
}

interface Ticket {
  id: string;
  title: string;
  description: string;
  priority: TicketPriority;
  client_id: string | null;
  project_id: string | null;
  assigned_to: string | null;
  category: string | null;
  tags: string[];
  deadline: string | null;
  [key: string]: any;
}

interface TicketFormDialogProps {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  ticket?: Ticket;
  onSubmit: (data: TicketFormData) => void;
  isLoading?: boolean;
}

export function TicketFormDialog({
  open,
  onOpenChange,
  ticket,
  onSubmit,
  isLoading,
}: TicketFormDialogProps) {
  const { t } = useI18n();
  const [clients, setClients] = useState<any[]>([]);
  const [projects, setProjects] = useState<any[]>([]);
  const [selectedClientId, setSelectedClientId] = useState<string>(
    ticket?.client_id ?? ""
  );

  const {
    register,
    handleSubmit,
    reset,
    watch,
    setValue,
    formState: { errors },
  } = useForm<TicketFormData>({
    defaultValues: {
      title: ticket?.title ?? "",
      description: ticket?.description ?? "",
      priority: ticket?.priority ?? "normal",
      category: ticket?.category ?? "",
      tags: ticket?.tags?.join(", ") ?? "",
      deadline: ticket?.deadline ?? "",
      assigned_to: ticket?.assigned_to ?? "",
    },
  });

  useEffect(() => {
    if (open) {
      apiClient
        .get<any>("/clients", { params: { per_page: 100 } })
        .then((r) => setClients(r.data.data ?? r.data))
        .catch(() => setClients([]));
    }
  }, [open]);

  useEffect(() => {
    if (selectedClientId) {
      apiClient
        .get<any>("/projects", {
          params: { client_id: selectedClientId, per_page: 100 },
        })
        .then((r) => setProjects(r.data.data ?? r.data))
        .catch(() => setProjects([]));
    } else {
      setProjects([]);
    }
  }, [selectedClientId]);

  useEffect(() => {
    if (open) {
      reset({
        title: ticket?.title ?? "",
        description: ticket?.description ?? "",
        priority: ticket?.priority ?? "normal",
        category: ticket?.category ?? "",
        tags: ticket?.tags?.join(", ") ?? "",
        deadline: ticket?.deadline ?? "",
        assigned_to: ticket?.assigned_to ?? "",
      });
      setSelectedClientId(ticket?.client_id ?? "");
    }
  }, [open, ticket, reset]);

  const handleFormSubmit = (data: TicketFormData) => {
    onSubmit({
      ...data,
      client_id: selectedClientId || undefined,
      project_id: data.project_id || undefined,
      tags: undefined,
    });
  };

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="max-w-2xl">
        <DialogHeader>
          <DialogTitle>
            {ticket
              ? t("tickets.form.titleEdit")
              : t("tickets.form.titleCreate")}
          </DialogTitle>
        </DialogHeader>
        <form
          onSubmit={handleSubmit(handleFormSubmit)}
          className="space-y-4 py-2"
        >
          <div className="space-y-2">
            <Label htmlFor="title">
              {t("tickets.form.fieldTitle")}{" "}
              <span className="text-red-500">*</span>
            </Label>
            <Input
              id="title"
              {...register("title", {
                required: t("tickets.form.fieldTitleRequired"),
              })}
              placeholder={t("tickets.form.fieldTitlePlaceholder")}
            />
            {errors.title && (
              <p className="text-sm text-red-500">{errors.title.message}</p>
            )}
          </div>

          <div className="space-y-2">
            <Label htmlFor="description">
              {t("tickets.form.fieldDescription")}{" "}
              <span className="text-red-500">*</span>
            </Label>
            <Textarea
              id="description"
              {...register("description", {
                required: t("tickets.form.fieldDescriptionRequired"),
              })}
              placeholder={t("tickets.form.fieldDescriptionPlaceholder")}
              rows={4}
            />
            {errors.description && (
              <p className="text-sm text-red-500">
                {errors.description.message}
              </p>
            )}
          </div>

          <div className="grid grid-cols-2 gap-4">
            <div className="space-y-2">
              <Label>{t("tickets.form.fieldClient")}</Label>
              <Select
                value={selectedClientId || "__none__"}
                onValueChange={(v) => {
                  setSelectedClientId(v === "__none__" ? "" : v);
                  setValue("project_id", "");
                }}
              >
                <SelectTrigger>
                  <SelectValue placeholder={t("tickets.form.noClient")} />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="__none__">
                    {t("tickets.form.noClient")}
                  </SelectItem>
                  {clients.map((c: any) => (
                    <SelectItem key={c.id} value={c.id}>
                      {c.name}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>

            <div className="space-y-2">
              <Label>{t("tickets.form.fieldProject")}</Label>
              <Select
                disabled={!selectedClientId}
                value={watch("project_id") ?? ""}
                onValueChange={(v) => setValue("project_id", v)}
              >
                <SelectTrigger>
                  <SelectValue
                    placeholder={
                      selectedClientId
                        ? t("tickets.form.selectProject")
                        : "Divers"
                    }
                  />
                </SelectTrigger>
                <SelectContent>
                  {projects.map((p: any) => (
                    <SelectItem key={p.id} value={p.id}>
                      {p.name}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>
          </div>

          <div className="grid grid-cols-2 gap-4">
            <div className="space-y-2">
              <Label>{t("tickets.form.fieldPriority")}</Label>
              <Select
                value={watch("priority")}
                onValueChange={(v) => setValue("priority", v as TicketPriority)}
              >
                <SelectTrigger>
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="low">
                    {t("tickets.priority.low")}
                  </SelectItem>
                  <SelectItem value="normal">
                    {t("tickets.priority.normal")}
                  </SelectItem>
                  <SelectItem value="high">
                    {t("tickets.priority.high")}
                  </SelectItem>
                  <SelectItem value="urgent">
                    {t("tickets.priority.urgent")}
                  </SelectItem>
                </SelectContent>
              </Select>
            </div>
            <div className="space-y-2">
              <Label htmlFor="assigned_to">
                {t("tickets.form.fieldAssignee")}
              </Label>
              <Input
                id="assigned_to"
                {...register("assigned_to")}
                placeholder={t("tickets.form.fieldAssigneePlaceholder")}
              />
            </div>
          </div>

          <div className="grid grid-cols-2 gap-4">
            <div className="space-y-2">
              <Label htmlFor="category">
                {t("tickets.form.fieldCategory")}
              </Label>
              <Input
                id="category"
                {...register("category")}
                placeholder={t("tickets.form.fieldCategoryPlaceholder")}
              />
            </div>
            <div className="space-y-2">
              <Label htmlFor="deadline">
                {t("tickets.form.fieldDeadline")}
              </Label>
              <Input id="deadline" type="date" {...register("deadline")} />
            </div>
          </div>

          <div className="space-y-2">
            <Label htmlFor="tags">{t("tickets.form.fieldTags")}</Label>
            <Input
              id="tags"
              {...register("tags")}
              placeholder={t("tickets.form.fieldTagsPlaceholder")}
            />
          </div>

          <DialogFooter>
            <Button
              type="button"
              variant="outline"
              onClick={() => onOpenChange(false)}
            >
              {t("tickets.form.cancel")}
            </Button>
            <Button type="submit" disabled={isLoading}>
              {isLoading
                ? t("tickets.form.saving")
                : ticket
                  ? t("tickets.form.saveChanges")
                  : t("tickets.form.create")}
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
}

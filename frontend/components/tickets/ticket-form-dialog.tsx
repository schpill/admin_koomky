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
          <DialogTitle>{ticket ? "Edit Ticket" : "New Ticket"}</DialogTitle>
        </DialogHeader>
        <form
          onSubmit={handleSubmit(handleFormSubmit)}
          className="space-y-4 py-2"
        >
          <div className="space-y-2">
            <Label htmlFor="title">
              Title <span className="text-red-500">*</span>
            </Label>
            <Input
              id="title"
              {...register("title", { required: "Title is required" })}
              placeholder="Ticket title"
            />
            {errors.title && (
              <p className="text-sm text-red-500">{errors.title.message}</p>
            )}
          </div>

          <div className="space-y-2">
            <Label htmlFor="description">
              Description <span className="text-red-500">*</span>
            </Label>
            <Textarea
              id="description"
              {...register("description", {
                required: "Description is required",
              })}
              placeholder="Describe the issue"
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
              <Label>Client</Label>
              <Select
                value={selectedClientId || "__none__"}
                onValueChange={(v) => {
                  setSelectedClientId(v === "__none__" ? "" : v);
                  setValue("project_id", "");
                }}
              >
                <SelectTrigger>
                  <SelectValue placeholder="No client (Divers)" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="__none__">No client (Divers)</SelectItem>
                  {clients.map((c: any) => (
                    <SelectItem key={c.id} value={c.id}>
                      {c.name}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>

            <div className="space-y-2">
              <Label>Project</Label>
              <Select
                disabled={!selectedClientId}
                value={watch("project_id") ?? ""}
                onValueChange={(v) => setValue("project_id", v)}
              >
                <SelectTrigger>
                  <SelectValue
                    placeholder={selectedClientId ? "Select project" : "Divers"}
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
              <Label>Priority</Label>
              <Select
                value={watch("priority")}
                onValueChange={(v) => setValue("priority", v as TicketPriority)}
              >
                <SelectTrigger>
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="low">Low</SelectItem>
                  <SelectItem value="normal">Normal</SelectItem>
                  <SelectItem value="high">High</SelectItem>
                  <SelectItem value="urgent">Urgent</SelectItem>
                </SelectContent>
              </Select>
            </div>
            <div className="space-y-2">
              <Label htmlFor="assigned_to">Assignee (user ID)</Label>
              <Input
                id="assigned_to"
                {...register("assigned_to")}
                placeholder="User ID"
              />
            </div>
          </div>

          <div className="grid grid-cols-2 gap-4">
            <div className="space-y-2">
              <Label htmlFor="category">Category</Label>
              <Input
                id="category"
                {...register("category")}
                placeholder="bug, billing, technical..."
              />
            </div>
            <div className="space-y-2">
              <Label htmlFor="deadline">Deadline</Label>
              <Input id="deadline" type="date" {...register("deadline")} />
            </div>
          </div>

          <div className="space-y-2">
            <Label htmlFor="tags">Tags (comma-separated)</Label>
            <Input
              id="tags"
              {...register("tags")}
              placeholder="tag1, tag2, tag3"
            />
          </div>

          <DialogFooter>
            <Button
              type="button"
              variant="outline"
              onClick={() => onOpenChange(false)}
            >
              Cancel
            </Button>
            <Button type="submit" disabled={isLoading}>
              {isLoading
                ? "Saving..."
                : ticket
                  ? "Save Changes"
                  : "Create Ticket"}
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
}

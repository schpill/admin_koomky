"use client";

import { useCallback, useEffect, useMemo, useState } from "react";
import { apiClient } from "@/lib/api";
import { Button } from "@/components/ui/button";
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from "@/components/ui/card";
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table";
import { Plus, Mail, Phone, Trash2, Edit2, Loader2, User } from "lucide-react";
import { toast } from "sonner";
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from "@/components/ui/dialog";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import { z } from "zod";
import { Badge } from "@/components/ui/badge";
import { useI18n } from "@/components/providers/i18n-provider";
import { ConfirmationDialog } from "@/components/common/confirmation-dialog";

type ContactFormData = {
  first_name: string;
  last_name?: string;
  email?: string;
  phone?: string;
  position?: string;
  is_primary: boolean;
};

interface Contact {
  id: string;
  first_name: string;
  last_name: string | null;
  email: string | null;
  phone: string | null;
  position: string | null;
  is_primary: boolean;
}

interface ClientContactListProps {
  clientId: string;
}

export function ClientContactList({ clientId }: ClientContactListProps) {
  const { t } = useI18n();
  const [contacts, setContacts] = useState<Contact[]>([]);
  const [loading, setLoading] = useState(true);
  const [dialogOpen, setDialogOpen] = useState(false);
  const [editingContact, setEditingContact] = useState<Contact | null>(null);
  const [deleteTarget, setDeleteTarget] = useState<Contact | null>(null);

  const contactSchema = useMemo(
    () =>
      z.object({
        first_name: z
          .string()
          .min(2, t("clients.contacts.validation.firstNameMin")),
        last_name: z.string().optional().or(z.literal("")),
        email: z
          .string()
          .email(t("auth.validation.invalidEmail"))
          .optional()
          .or(z.literal("")),
        phone: z.string().optional().or(z.literal("")),
        position: z.string().optional().or(z.literal("")),
        is_primary: z.boolean().default(false),
      }),
    [t]
  );

  const {
    register,
    handleSubmit,
    reset,
    formState: { errors, isSubmitting },
    setValue,
  } = useForm<ContactFormData>({
    resolver: zodResolver(contactSchema),
    defaultValues: {
      is_primary: false,
    },
  });

  const fetchContacts = useCallback(async () => {
    try {
      const response = await apiClient.get<Contact[]>(
        `/clients/${clientId}/contacts`
      );
      setContacts(response.data);
    } catch (error) {
      toast.error(t("clients.contacts.toasts.loadFailed"));
    } finally {
      setLoading(false);
    }
  }, [clientId, t]);

  useEffect(() => {
    fetchContacts();
  }, [fetchContacts]);

  const onSubmit = async (data: ContactFormData) => {
    try {
      if (editingContact) {
        await apiClient.put(
          `/clients/${clientId}/contacts/${editingContact.id}`,
          data
        );
        toast.success(t("clients.contacts.toasts.updated"));
      } else {
        await apiClient.post(`/clients/${clientId}/contacts`, data);
        toast.success(t("clients.contacts.toasts.added"));
      }
      setDialogOpen(false);
      reset();
      setEditingContact(null);
      fetchContacts();
    } catch (error) {
      toast.error(t("clients.contacts.toasts.saveFailed"));
    }
  };

  const handleEdit = (contact: Contact) => {
    setEditingContact(contact);
    setValue("first_name", contact.first_name);
    setValue("last_name", contact.last_name || "");
    setValue("email", contact.email || "");
    setValue("phone", contact.phone || "");
    setValue("position", contact.position || "");
    setValue("is_primary", contact.is_primary);
    setDialogOpen(true);
  };

  const handleDelete = async (contactId: string) => {
    try {
      await apiClient.delete(`/clients/${clientId}/contacts/${contactId}`);
      toast.success(t("clients.contacts.toasts.deleted"));
      fetchContacts();
    } catch (error) {
      toast.error(t("clients.contacts.toasts.deleteFailed"));
    }
  };

  if (loading) {
    return (
      <div className="flex justify-center p-8">
        <Loader2 className="h-8 w-8 animate-spin text-primary" />
      </div>
    );
  }

  return (
    <Card>
      <CardHeader className="flex flex-row items-center justify-between">
        <div>
          <CardTitle>{t("clients.contacts.title")}</CardTitle>
          <CardDescription>{t("clients.contacts.description")}</CardDescription>
        </div>
        <Dialog
          open={dialogOpen}
          onOpenChange={(open) => {
            setDialogOpen(open);
            if (!open) {
              reset();
              setEditingContact(null);
            }
          }}
        >
          <DialogTrigger asChild>
            <Button size="sm">
              <Plus className="mr-2 h-4 w-4" />{" "}
              {t("clients.contacts.addContact")}
            </Button>
          </DialogTrigger>
          <DialogContent>
            <DialogHeader>
              <DialogTitle>
                {editingContact
                  ? t("clients.contacts.editContact")
                  : t("clients.contacts.addNewContact")}
              </DialogTitle>
            </DialogHeader>
            <form onSubmit={handleSubmit(onSubmit)} className="space-y-4 py-4">
              <div className="grid grid-cols-2 gap-4">
                <div className="space-y-2">
                  <Label htmlFor="first_name">
                    {t("clients.contacts.firstName")}
                  </Label>
                  <Input
                    id="first_name"
                    {...register("first_name")}
                    aria-invalid={Boolean(errors.first_name)}
                    aria-describedby={
                      errors.first_name ? "first_name-error" : undefined
                    }
                  />
                  {errors.first_name && (
                    <p
                      id="first_name-error"
                      className="text-sm text-destructive"
                    >
                      {errors.first_name.message}
                    </p>
                  )}
                </div>
                <div className="space-y-2">
                  <Label htmlFor="last_name">
                    {t("clients.contacts.lastName")}
                  </Label>
                  <Input id="last_name" {...register("last_name")} />
                </div>
              </div>
              <div className="space-y-2">
                <Label htmlFor="contact-position">
                  {t("clients.contacts.position")}
                </Label>
                <Input id="contact-position" {...register("position")} />
              </div>
              <div className="space-y-2">
                <Label htmlFor="contact-email">
                  {t("clients.table.email")}
                </Label>
                <Input
                  id="contact-email"
                  type="email"
                  {...register("email")}
                  aria-invalid={Boolean(errors.email)}
                  aria-describedby={
                    errors.email ? "contact-email-error" : undefined
                  }
                />
                {errors.email && (
                  <p
                    id="contact-email-error"
                    className="text-sm text-destructive"
                  >
                    {errors.email.message}
                  </p>
                )}
              </div>
              <div className="space-y-2">
                <Label htmlFor="contact-phone">{t("clients.form.phone")}</Label>
                <Input id="contact-phone" {...register("phone")} />
              </div>
              <div className="flex items-center space-x-2">
                <input
                  type="checkbox"
                  id="is_primary"
                  {...register("is_primary")}
                  className="h-4 w-4 rounded border-input text-primary focus:ring-primary"
                />
                <Label htmlFor="is_primary">
                  {t("clients.contacts.primaryContact")}
                </Label>
              </div>
              <div className="flex justify-end gap-2 pt-4">
                <Button
                  type="button"
                  variant="outline"
                  onClick={() => setDialogOpen(false)}
                >
                  {t("common.cancel")}
                </Button>
                <Button type="submit" disabled={isSubmitting}>
                  {isSubmitting && (
                    <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                  )}
                  {editingContact ? t("common.update") : t("common.save")}
                </Button>
              </div>
            </form>
          </DialogContent>
        </Dialog>
      </CardHeader>
      <CardContent>
        {contacts.length === 0 ? (
          <div className="flex flex-col items-center justify-center py-8 text-center">
            <User className="h-12 w-12 text-muted-foreground/50 mb-4" />
            <p className="text-muted-foreground">
              {t("clients.contacts.empty")}
            </p>
          </div>
        ) : (
          <div className="overflow-x-auto">
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>{t("clients.table.name")}</TableHead>
                  <TableHead>{t("clients.contacts.position")}</TableHead>
                  <TableHead>{t("clients.contacts.contactInfo")}</TableHead>
                  <TableHead className="text-right">
                    {t("clients.table.actions")}
                  </TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {contacts.map((contact) => (
                  <TableRow key={contact.id}>
                    <TableCell className="font-medium">
                      <div className="flex items-center gap-2">
                        {contact.first_name} {contact.last_name}
                        {contact.is_primary && (
                          <Badge variant="secondary" className="text-[10px]">
                            {t("clients.contacts.primary")}
                          </Badge>
                        )}
                      </div>
                    </TableCell>
                    <TableCell>{contact.position || "-"}</TableCell>
                    <TableCell>
                      <div className="space-y-1">
                        {contact.email && (
                          <div className="flex items-center text-xs text-muted-foreground">
                            <Mail className="mr-1 h-3 w-3" /> {contact.email}
                          </div>
                        )}
                        {contact.phone && (
                          <div className="flex items-center text-xs text-muted-foreground">
                            <Phone className="mr-1 h-3 w-3" /> {contact.phone}
                          </div>
                        )}
                      </div>
                    </TableCell>
                    <TableCell className="text-right">
                      <div className="flex justify-end gap-2">
                        <Button
                          variant="ghost"
                          size="icon"
                          onClick={() => handleEdit(contact)}
                        >
                          <Edit2 className="h-4 w-4" />
                        </Button>
                        <Button
                          variant="ghost"
                          size="icon"
                          className="text-destructive"
                          onClick={() => setDeleteTarget(contact)}
                        >
                          <Trash2 className="h-4 w-4" />
                        </Button>
                      </div>
                    </TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          </div>
        )}
      </CardContent>
      <ConfirmationDialog
        open={deleteTarget !== null}
        onOpenChange={(open) => {
          if (!open) {
            setDeleteTarget(null);
          }
        }}
        onConfirm={() => {
          if (!deleteTarget) {
            return;
          }

          handleDelete(deleteTarget.id);
          setDeleteTarget(null);
        }}
        title={t("clients.contacts.deleteContact")}
        description={t("clients.contacts.deleteConfirmation")}
        confirmText={t("clients.contacts.deleteContact")}
        variant="destructive"
      />
    </Card>
  );
}

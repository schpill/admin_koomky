"use client";

import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import { z } from "zod";
import { useMemo } from "react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Loader2 } from "lucide-react";
import { useI18n } from "@/components/providers/i18n-provider";

export type ClientFormData = {
  name: string;
  email?: string | null;
  phone?: string | null;
  address?: string | null;
  city?: string | null;
  zip_code?: string | null;
  country?: string | null;
};

interface ClientFormProps {
  initialData?: Partial<ClientFormData>;
  onSubmit: (data: ClientFormData) => Promise<void>;
  onCancel: () => void;
  submitLabel?: string;
}

export function ClientForm({
  initialData,
  onSubmit,
  onCancel,
  submitLabel,
}: ClientFormProps) {
  const { t } = useI18n();
  const defaultSubmitLabel = submitLabel ?? t("clients.form.saveClient");

  const clientSchema = useMemo(
    () =>
      z.object({
        name: z.string().min(2, t("clients.form.validation.nameMin")),
        email: z
          .string()
          .email(t("auth.validation.invalidEmail"))
          .optional()
          .or(z.literal(""))
          .or(z.null()),
        phone: z.string().optional().nullable(),
        address: z.string().optional().nullable(),
        city: z.string().optional().nullable(),
        zip_code: z.string().optional().nullable(),
        country: z.string().optional().nullable(),
      }),
    [t]
  );

  const {
    register,
    handleSubmit,
    formState: { errors, isSubmitting },
  } = useForm<ClientFormData>({
    resolver: zodResolver(clientSchema),
    defaultValues: initialData,
  });

  return (
    <form onSubmit={handleSubmit(onSubmit)} className="space-y-4">
      <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div className="md:col-span-2 space-y-2">
          <Label htmlFor="name">{t("clients.form.companyOrClientName")}</Label>
          <Input id="name" {...register("name")} disabled={isSubmitting} />
          {errors.name && (
            <p className="text-sm text-destructive">{errors.name.message}</p>
          )}
        </div>
        <div className="space-y-2">
          <Label htmlFor="email">{t("clients.table.email")}</Label>
          <Input
            id="email"
            type="email"
            {...register("email")}
            disabled={isSubmitting}
          />
          {errors.email && (
            <p className="text-sm text-destructive">{errors.email.message}</p>
          )}
        </div>
        <div className="space-y-2">
          <Label htmlFor="phone">{t("clients.form.phone")}</Label>
          <Input id="phone" {...register("phone")} disabled={isSubmitting} />
        </div>
        <div className="md:col-span-2 space-y-2">
          <Label htmlFor="address">{t("clients.detail.address")}</Label>
          <Input
            id="address"
            {...register("address")}
            disabled={isSubmitting}
          />
        </div>
        <div className="space-y-2">
          <Label htmlFor="city">{t("clients.form.city")}</Label>
          <Input id="city" {...register("city")} disabled={isSubmitting} />
        </div>
        <div className="space-y-2">
          <Label htmlFor="zip_code">{t("clients.form.zipCode")}</Label>
          <Input
            id="zip_code"
            {...register("zip_code")}
            disabled={isSubmitting}
          />
        </div>
        <div className="md:col-span-2 space-y-2">
          <Label htmlFor="country">{t("clients.form.country")}</Label>
          <Input
            id="country"
            {...register("country")}
            disabled={isSubmitting}
          />
        </div>
      </div>
      <div className="flex justify-end gap-4 pt-4">
        <Button type="button" variant="outline" onClick={onCancel}>
          {t("common.cancel")}
        </Button>
        <Button type="submit" disabled={isSubmitting}>
          {isSubmitting && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
          {defaultSubmitLabel}
        </Button>
      </div>
    </form>
  );
}

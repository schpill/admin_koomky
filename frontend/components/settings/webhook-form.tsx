"use client";

import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import { z } from "zod";
import { useMemo } from "react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Loader2, RefreshCw } from "lucide-react";
import { useI18n } from "@/components/providers/i18n-provider";
import { cn } from "@/lib/utils";
import {
  WEBHOOK_EVENTS,
  type WebhookEvent,
} from "@/lib/constants/webhook-events";

export { WEBHOOK_EVENTS, type WebhookEvent };

export type WebhookFormData = {
  name: string;
  url: string;
  events: WebhookEvent[];
  secret?: string;
  is_active: boolean;
};

interface WebhookFormProps {
  initialData?: Partial<WebhookFormData>;
  onSubmit: (data: WebhookFormData) => Promise<void>;
  onCancel: () => void;
  submitLabel?: string;
  isEditMode?: boolean;
}

function generateSecret(): string {
  const array = new Uint8Array(32);
  crypto.getRandomValues(array);
  return Array.from(array, (b) => b.toString(16).padStart(2, "0")).join("");
}

export function WebhookForm({
  initialData,
  onSubmit,
  onCancel,
  submitLabel,
  isEditMode = false,
}: WebhookFormProps) {
  const { t } = useI18n();
  const defaultSubmitLabel = submitLabel ?? t("common.save");

  const webhookSchema = useMemo(
    () =>
      z.object({
        name: z.string().min(2, "Name must be at least 2 characters"),
        url: z
          .string()
          .url("Must be a valid URL")
          .startsWith("https://", "URL must start with https://"),
        events: z
          .array(z.enum(WEBHOOK_EVENTS))
          .min(1, "Select at least one event"),
        secret: z.string().optional(),
        is_active: z.boolean(),
      }),
    []
  );

  const {
    register,
    handleSubmit,
    control,
    watch,
    setValue,
    formState: { errors, isSubmitting },
  } = useForm<WebhookFormData>({
    resolver: zodResolver(webhookSchema),
    defaultValues: {
      name: initialData?.name ?? "",
      url: initialData?.url ?? "",
      events: initialData?.events ?? [],
      secret: initialData?.secret ?? "",
      is_active: initialData?.is_active ?? true,
    },
  });

  const selectedEvents = watch("events");
  const isActive = watch("is_active");
  const secretValue = watch("secret");

  const toggleEvent = (event: WebhookEvent) => {
    const currentEvents = selectedEvents ?? [];
    const newEvents = currentEvents.includes(event)
      ? currentEvents.filter((e) => e !== event)
      : [...currentEvents, event];
    setValue("events", newEvents as WebhookEvent[], { shouldValidate: true });
  };

  const handleGenerateSecret = () => {
    setValue("secret", generateSecret(), { shouldDirty: true });
  };

  const handleToggleActive = () => {
    setValue("is_active", !isActive, { shouldDirty: true });
  };

  return (
    <form onSubmit={handleSubmit(onSubmit)} className="space-y-6">
      <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div className="space-y-2">
          <Label htmlFor="webhook-name">
            {t("settings.webhooks.form.name")}
          </Label>
          <Input
            id="webhook-name"
            {...register("name")}
            disabled={isSubmitting}
            placeholder="My Webhook"
          />
          {errors.name && (
            <p className="text-sm text-destructive">{errors.name.message}</p>
          )}
        </div>

        <div className="space-y-2">
          <Label htmlFor="webhook-url">{t("settings.webhooks.form.url")}</Label>
          <Input
            id="webhook-url"
            type="url"
            {...register("url")}
            disabled={isSubmitting}
            placeholder="https://example.com/webhook"
          />
          {errors.url && (
            <p className="text-sm text-destructive">{errors.url.message}</p>
          )}
        </div>
      </div>

      <div className="space-y-2">
        <Label htmlFor="webhook-secret">
          {t("settings.webhooks.form.secret")}
          <span className="ml-2 text-xs text-muted-foreground">
            ({t("settings.webhooks.form.secretHint")})
          </span>
        </Label>
        <div className="flex gap-2">
          <Input
            id="webhook-secret"
            {...register("secret")}
            disabled={isSubmitting}
            placeholder={t("settings.webhooks.form.secretPlaceholder")}
            className="flex-1"
            value={secretValue}
          />
          <Button
            type="button"
            variant="outline"
            onClick={handleGenerateSecret}
            disabled={isSubmitting}
            title={t("settings.webhooks.form.generateSecret")}
          >
            <RefreshCw className="h-4 w-4" />
          </Button>
        </div>
        {errors.secret && (
          <p className="text-sm text-destructive">{errors.secret.message}</p>
        )}
      </div>

      <div className="space-y-2">
        <Label>{t("settings.webhooks.form.events")}</Label>
        <div className="grid max-h-60 gap-1 overflow-y-auto md:grid-cols-2 lg:grid-cols-3">
          {WEBHOOK_EVENTS.map((event) => (
            <label
              key={event}
              className={cn(
                "flex cursor-pointer items-center gap-2 rounded border p-2 text-sm hover:bg-muted",
                selectedEvents?.includes(event) && "border-primary bg-muted/50"
              )}
            >
              <input
                type="checkbox"
                checked={selectedEvents?.includes(event) ?? false}
                onChange={() => toggleEvent(event)}
                className="h-4 w-4"
                disabled={isSubmitting}
              />
              <span className="font-mono text-xs">{event}</span>
            </label>
          ))}
        </div>
        {errors.events && (
          <p className="text-sm text-destructive">{errors.events.message}</p>
        )}
      </div>

      <div className="flex items-center gap-2">
        <button
          type="button"
          onClick={handleToggleActive}
          disabled={isSubmitting}
          className={cn(
            "relative inline-flex h-6 w-11 items-center rounded-full transition-colors",
            isActive ? "bg-primary" : "bg-input"
          )}
        >
          <span
            className={cn(
              "inline-block h-4 w-4 transform rounded-full bg-white transition-transform",
              isActive ? "translate-x-6" : "translate-x-1"
            )}
          />
        </button>
        <Label className="cursor-pointer" onClick={handleToggleActive}>
          {isActive
            ? t("settings.webhooks.form.active")
            : t("settings.webhooks.form.inactive")}
        </Label>
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

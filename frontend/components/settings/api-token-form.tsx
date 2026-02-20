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
import { cn } from "@/lib/utils";
import { API_SCOPES, type ApiScope } from "@/lib/constants/api-scopes";

export { API_SCOPES, type ApiScope };

export type ApiTokenFormData = {
  name: string;
  abilities: ApiScope[];
  expires_at?: string;
};

interface ApiTokenFormProps {
  initialData?: Partial<ApiTokenFormData>;
  onSubmit: (data: ApiTokenFormData) => Promise<void>;
  onCancel: () => void;
  submitLabel?: string;
}

export function ApiTokenForm({
  initialData,
  onSubmit,
  onCancel,
  submitLabel,
}: ApiTokenFormProps) {
  const { t } = useI18n();
  const defaultSubmitLabel = submitLabel ?? t("settings.apiTokens.createToken");

  const apiTokenSchema = useMemo(
    () =>
      z.object({
        name: z.string().min(2, "Name must be at least 2 characters"),
        abilities: z.array(z.string()).min(1, "Select at least one scope"),
        expires_at: z.string().optional(),
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
  } = useForm<ApiTokenFormData>({
    resolver: zodResolver(apiTokenSchema),
    defaultValues: {
      name: initialData?.name ?? "",
      abilities: (initialData?.abilities as ApiScope[]) ?? [],
      expires_at: initialData?.expires_at ?? "",
    },
  });

  const selectedAbilities = watch("abilities");

  const toggleScope = (scope: ApiScope) => {
    const currentAbilities = selectedAbilities ?? [];
    const newAbilities = currentAbilities.includes(scope)
      ? currentAbilities.filter((s) => s !== scope)
      : [...currentAbilities, scope];
    setValue("abilities", newAbilities as ApiScope[], {
      shouldValidate: true,
    });
  };

  const selectAllRead = () => {
    const readScopes = API_SCOPES.filter((s) => s.name.startsWith("read:")).map(
      (s) => s.name as ApiScope
    );
    setValue("abilities", readScopes, { shouldValidate: true });
  };

  const selectAll = () => {
    setValue(
      "abilities",
      API_SCOPES.map((s) => s.name as ApiScope),
      { shouldValidate: true }
    );
  };

  const clearAll = () => {
    setValue("abilities", [], { shouldValidate: true });
  };

  const today = new Date().toISOString().split("T")[0];

  return (
    <form onSubmit={handleSubmit(onSubmit)} className="space-y-6">
      <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div className="space-y-2">
          <Label htmlFor="token-name">
            {t("settings.apiTokens.form.name")}
          </Label>
          <Input
            id="token-name"
            {...register("name")}
            disabled={isSubmitting}
            placeholder="My API Token"
          />
          {errors.name && (
            <p className="text-sm text-destructive">{errors.name.message}</p>
          )}
        </div>

        <div className="space-y-2">
          <Label htmlFor="token-expires">
            {t("settings.apiTokens.form.expiresAt")}
          </Label>
          <Input
            id="token-expires"
            type="date"
            {...register("expires_at")}
            disabled={isSubmitting}
            min={today}
          />
          {errors.expires_at && (
            <p className="text-sm text-destructive">
              {errors.expires_at.message}
            </p>
          )}
        </div>
      </div>

      <div className="space-y-3">
        <div className="flex items-center justify-between">
          <Label>{t("settings.apiTokens.form.scopes")}</Label>
          <div className="flex gap-2">
            <Button
              type="button"
              variant="ghost"
              size="sm"
              onClick={selectAllRead}
              disabled={isSubmitting}
            >
              {t("settings.apiTokens.form.selectAllRead")}
            </Button>
            <Button
              type="button"
              variant="ghost"
              size="sm"
              onClick={selectAll}
              disabled={isSubmitting}
            >
              {t("settings.apiTokens.form.selectAll")}
            </Button>
            <Button
              type="button"
              variant="ghost"
              size="sm"
              onClick={clearAll}
              disabled={isSubmitting}
            >
              {t("settings.apiTokens.form.clearAll")}
            </Button>
          </div>
        </div>
        <div className="grid gap-2 md:grid-cols-2 lg:grid-cols-3">
          {API_SCOPES.map((scope) => (
            <label
              key={scope.name}
              className={cn(
                "flex cursor-pointer items-start gap-2 rounded border p-3 hover:bg-muted",
                selectedAbilities?.includes(scope.name as ApiScope) &&
                  "border-primary bg-muted/50"
              )}
            >
              <input
                type="checkbox"
                checked={
                  selectedAbilities?.includes(scope.name as ApiScope) ?? false
                }
                onChange={() => toggleScope(scope.name as ApiScope)}
                className="mt-0.5 h-4 w-4"
                disabled={isSubmitting}
              />
              <div>
                <p className="text-sm font-medium font-mono">{scope.name}</p>
                <p className="text-xs text-muted-foreground">
                  {scope.description}
                </p>
              </div>
            </label>
          ))}
        </div>
        {errors.abilities && (
          <p className="text-sm text-destructive">{errors.abilities.message}</p>
        )}
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

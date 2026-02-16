"use client";

import { useEffect } from "react";
import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import { z } from "zod";
import { useMemo } from "react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import {
  Card,
  CardContent,
  CardDescription,
  CardFooter,
  CardHeader,
  CardTitle,
} from "@/components/ui/card";
import { DashboardLayout } from "@/components/layout/dashboard-layout";
import { apiClient } from "@/lib/api";
import { useAuthStore } from "@/lib/stores/auth";
import { toast } from "sonner";
import { useI18n } from "@/components/providers/i18n-provider";

type ProfileFormData = {
  name: string;
  email: string;
};

export default function ProfileSettingsPage() {
  const { user, setUser } = useAuthStore();
  const { t } = useI18n();

  const profileSchema = useMemo(
    () =>
      z.object({
        name: z.string().min(2, t("auth.validation.fullNameMin")),
        email: z.string().email(t("auth.validation.invalidEmail")),
      }),
    [t]
  );

  const {
    register,
    handleSubmit,
    reset,
    formState: { errors, isSubmitting, isDirty },
  } = useForm<ProfileFormData>({
    resolver: zodResolver(profileSchema),
    defaultValues: {
      name: user?.name || "",
      email: user?.email || "",
    },
  });

  // Sync with store if it rehydrates after page load
  useEffect(() => {
    if (user) {
      reset({
        name: user.name,
        email: user.email,
      });
    }
  }, [user, reset]);

  const onSubmit = async (data: ProfileFormData) => {
    try {
      const result = await apiClient.put<any>("/settings/profile", data);
      setUser(result.data);
      toast.success(t("settings.profile.toasts.updated"));
      reset(data); // Mark as pristine
    } catch (error) {
      toast.error(
        error instanceof Error
          ? error.message
          : t("settings.common.updateFailed")
      );
    }
  };

  return (
    <DashboardLayout>
      <div className="max-w-2xl mx-auto space-y-6">
        <h1 className="text-3xl font-bold">{t("settings.common.title")}</h1>

        <Card>
          <CardHeader>
            <CardTitle>{t("settings.profile.cardTitle")}</CardTitle>
            <CardDescription>
              {t("settings.profile.cardDescription")}
            </CardDescription>
          </CardHeader>
          <form onSubmit={handleSubmit(onSubmit)}>
            <CardContent className="space-y-4">
              <div className="space-y-2">
                <Label htmlFor="name">{t("auth.register.fullName")}</Label>
                <Input
                  id="name"
                  {...register("name")}
                  disabled={isSubmitting}
                />
                {errors.name && (
                  <p className="text-sm text-destructive">
                    {errors.name.message}
                  </p>
                )}
              </div>
              <div className="space-y-2">
                <Label htmlFor="email">{t("auth.login.email")}</Label>
                <Input
                  id="email"
                  type="email"
                  {...register("email")}
                  disabled={isSubmitting}
                />
                {errors.email && (
                  <p className="text-sm text-destructive">
                    {errors.email.message}
                  </p>
                )}
              </div>
            </CardContent>
            <CardFooter className="border-t px-6 py-4 flex justify-end bg-muted/50">
              <Button type="submit" disabled={isSubmitting || !isDirty}>
                {isSubmitting
                  ? t("settings.common.saving")
                  : t("settings.common.saveChanges")}
              </Button>
            </CardFooter>
          </form>
        </Card>
      </div>
    </DashboardLayout>
  );
}

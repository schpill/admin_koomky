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

type BusinessFormData = {
  business_name: string;
};

export default function BusinessSettingsPage() {
  const { user, setUser } = useAuthStore();
  const { t } = useI18n();

  const businessSchema = useMemo(
    () =>
      z.object({
        business_name: z.string().min(2, t("auth.validation.businessNameMin")),
      }),
    [t]
  );

  const {
    register,
    handleSubmit,
    reset,
    formState: { errors, isSubmitting, isDirty },
  } = useForm<BusinessFormData>({
    resolver: zodResolver(businessSchema),
    defaultValues: {
      business_name: user?.business_name || "",
    },
  });

  useEffect(() => {
    if (user) {
      reset({
        business_name: user.business_name || "",
      });
    }
  }, [user, reset]);

  const onSubmit = async (data: BusinessFormData) => {
    try {
      const result = await apiClient.put<any>("/settings/business", data);
      setUser(result.data);
      toast.success(t("settings.business.toasts.updated"));
      reset(data);
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
            <CardTitle>{t("settings.business.cardTitle")}</CardTitle>
            <CardDescription>
              {t("settings.business.cardDescription")}
            </CardDescription>
          </CardHeader>
          <form onSubmit={handleSubmit(onSubmit)}>
            <CardContent className="space-y-4">
              <div className="space-y-2">
                <Label htmlFor="business_name">
                  {t("auth.register.businessName")}
                </Label>
                <Input
                  id="business_name"
                  {...register("business_name")}
                  disabled={isSubmitting}
                />
                {errors.business_name && (
                  <p className="text-sm text-destructive">
                    {errors.business_name.message}
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

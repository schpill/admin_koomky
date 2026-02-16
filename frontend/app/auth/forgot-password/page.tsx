"use client";

import Link from "next/link";
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
import { apiClient } from "@/lib/api";
import { toast } from "sonner";
import { ChevronLeft } from "lucide-react";
import { useI18n } from "@/components/providers/i18n-provider";

type ForgotPasswordFormData = {
  email: string;
};

export default function ForgotPasswordPage() {
  const { t } = useI18n();

  const forgotPasswordSchema = useMemo(
    () =>
      z.object({
        email: z.string().email(t("auth.validation.invalidEmail")),
      }),
    [t]
  );

  const {
    register,
    handleSubmit,
    formState: { errors, isSubmitting },
  } = useForm<ForgotPasswordFormData>({
    resolver: zodResolver(forgotPasswordSchema),
  });

  const onSubmit = async (data: ForgotPasswordFormData) => {
    try {
      const result = await apiClient.post("/auth/forgot-password", data, {
        skipAuth: true,
      });
      toast.success(result.message || t("auth.forgotPassword.toasts.success"));
    } catch (error) {
      toast.error(
        error instanceof Error
          ? error.message
          : t("auth.forgotPassword.toasts.failed")
      );
    }
  };

  return (
    <Card>
      <CardHeader className="space-y-1">
        <CardTitle className="text-2xl font-bold text-center">
          {t("auth.forgotPassword.title")}
        </CardTitle>
        <CardDescription className="text-center">
          {t("auth.forgotPassword.description")}
        </CardDescription>
      </CardHeader>
      <form onSubmit={handleSubmit(onSubmit)}>
        <CardContent className="space-y-4">
          <div className="space-y-2">
            <Label htmlFor="email">{t("auth.forgotPassword.email")}</Label>
            <Input
              id="email"
              type="email"
              placeholder={t("auth.forgotPassword.emailPlaceholder")}
              {...register("email")}
              disabled={isSubmitting}
            />
            {errors.email && (
              <p className="text-sm text-destructive">{errors.email.message}</p>
            )}
          </div>
        </CardContent>
        <CardFooter className="flex flex-col gap-4">
          <Button type="submit" className="w-full" disabled={isSubmitting}>
            {isSubmitting
              ? t("auth.forgotPassword.sending")
              : t("auth.forgotPassword.submit")}
          </Button>
          <Link
            href="/auth/login"
            className="inline-flex items-center gap-2 text-sm text-muted-foreground hover:text-primary mx-auto"
          >
            <ChevronLeft className="h-4 w-4" />
            {t("auth.forgotPassword.backToLogin")}
          </Link>
        </CardFooter>
      </form>
    </Card>
  );
}

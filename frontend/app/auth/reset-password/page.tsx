"use client";

import { useRouter, useSearchParams } from "next/navigation";
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
import { Suspense } from "react";
import { useI18n } from "@/components/providers/i18n-provider";

type ResetPasswordFormData = {
  token: string;
  email: string;
  password: string;
  password_confirmation: string;
};

function ResetPasswordForm() {
  const router = useRouter();
  const searchParams = useSearchParams();
  const { t } = useI18n();

  const resetPasswordSchema = useMemo(
    () =>
      z
        .object({
          token: z.string().min(1, t("auth.validation.tokenMissing")),
          email: z.string().email(t("auth.validation.invalidEmail")),
          password: z.string().min(8, t("auth.validation.minPassword")),
          password_confirmation: z.string(),
        })
        .refine((data) => data.password === data.password_confirmation, {
          message: t("auth.validation.passwordsMismatch"),
          path: ["password_confirmation"],
        }),
    [t],
  );

  const {
    register,
    handleSubmit,
    formState: { errors, isSubmitting },
  } = useForm<ResetPasswordFormData>({
    resolver: zodResolver(resetPasswordSchema),
    defaultValues: {
      token: searchParams.get("token") || "",
      email: searchParams.get("email") || "",
    },
  });

  const onSubmit = async (data: ResetPasswordFormData) => {
    try {
      const result = await apiClient.post("/auth/reset-password", data, {
        skipAuth: true,
      });
      toast.success(result.message || t("auth.resetPassword.toasts.success"));
      router.push("/auth/login");
    } catch (error) {
      toast.error(
        error instanceof Error
          ? error.message
          : t("auth.resetPassword.toasts.failed"),
      );
    }
  };

  return (
    <Card>
      <CardHeader className="space-y-1">
        <CardTitle className="text-2xl font-bold text-center">
          {t("auth.resetPassword.title")}
        </CardTitle>
        <CardDescription className="text-center">
          {t("auth.resetPassword.description")}
        </CardDescription>
      </CardHeader>
      <form onSubmit={handleSubmit(onSubmit)}>
        <CardContent className="space-y-4">
          <Input type="hidden" {...register("token")} />
          <Input type="hidden" {...register("email")} />

          <div className="space-y-2">
            <Label htmlFor="password">{t("auth.resetPassword.newPassword")}</Label>
            <Input
              id="password"
              type="password"
              {...register("password")}
              disabled={isSubmitting}
            />
            {errors.password && (
              <p className="text-sm text-destructive">
                {errors.password.message}
              </p>
            )}
          </div>

          <div className="space-y-2">
            <Label htmlFor="password_confirmation">
              {t("auth.resetPassword.confirmNewPassword")}
            </Label>
            <Input
              id="password_confirmation"
              type="password"
              {...register("password_confirmation")}
              disabled={isSubmitting}
            />
            {errors.password_confirmation && (
              <p className="text-sm text-destructive">
                {errors.password_confirmation.message}
              </p>
            )}
          </div>
        </CardContent>
        <CardFooter>
          <Button type="submit" className="w-full" disabled={isSubmitting}>
            {isSubmitting
              ? t("auth.resetPassword.resetting")
              : t("auth.resetPassword.submit")}
          </Button>
        </CardFooter>
      </form>
    </Card>
  );
}

export default function ResetPasswordPage() {
  const { t } = useI18n();

  return (
    <Suspense fallback={<div>{t("common.loading")}</div>}>
      <ResetPasswordForm />
    </Suspense>
  );
}

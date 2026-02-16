"use client";

import Link from "next/link";
import { useRouter } from "next/navigation";
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
import { useAuthStore } from "@/lib/stores/auth";
import { apiClient } from "@/lib/api";
import { toast } from "sonner";
import { useI18n } from "@/components/providers/i18n-provider";

type RegisterFormData = {
  name: string;
  email: string;
  business_name: string;
  password: string;
  password_confirmation: string;
};

export default function RegisterPage() {
  const router = useRouter();
  const setAuth = useAuthStore((state) => state.setAuth);
  const { t } = useI18n();

  const registerSchema = useMemo(
    () =>
      z
        .object({
          name: z.string().min(2, t("auth.validation.fullNameMin")),
          email: z.string().email(t("auth.validation.invalidEmail")),
          business_name: z
            .string()
            .min(2, t("auth.validation.businessNameMin")),
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
  } = useForm<RegisterFormData>({
    resolver: zodResolver(registerSchema),
  });

  const onSubmit = async (data: RegisterFormData) => {
    try {
      const result = await apiClient.post<{
        user: any;
        access_token: string;
        refresh_token: string;
      }>("/auth/register", data, { skipAuth: true });

      setAuth(
        result.data.user,
        result.data.access_token,
        result.data.refresh_token,
      );
      toast.success(t("auth.register.toasts.success"));
      router.push("/");
    } catch (error) {
      toast.error(
        error instanceof Error ? error.message : t("auth.register.toasts.failed"),
      );
    }
  };

  return (
    <Card>
      <CardHeader className="space-y-1">
        <CardTitle className="text-2xl font-bold text-center">
          {t("auth.register.title")}
        </CardTitle>
        <CardDescription className="text-center">
          {t("auth.register.description")}
        </CardDescription>
      </CardHeader>
      <form onSubmit={handleSubmit(onSubmit)}>
        <CardContent className="space-y-4">
          <div className="space-y-2">
            <Label htmlFor="name">{t("auth.register.fullName")}</Label>
            <Input
              id="name"
              placeholder={t("auth.register.fullNamePlaceholder")}
              {...register("name")}
              disabled={isSubmitting}
            />
            {errors.name && (
              <p className="text-sm text-destructive">{errors.name.message}</p>
            )}
          </div>
          <div className="space-y-2">
            <Label htmlFor="email">{t("auth.register.email")}</Label>
            <Input
              id="email"
              type="email"
              placeholder={t("auth.register.emailPlaceholder")}
              {...register("email")}
              disabled={isSubmitting}
            />
            {errors.email && (
              <p className="text-sm text-destructive">{errors.email.message}</p>
            )}
          </div>
          <div className="space-y-2">
            <Label htmlFor="business_name">{t("auth.register.businessName")}</Label>
            <Input
              id="business_name"
              placeholder={t("auth.register.businessNamePlaceholder")}
              {...register("business_name")}
              disabled={isSubmitting}
            />
            {errors.business_name && (
              <p className="text-sm text-destructive">
                {errors.business_name.message}
              </p>
            )}
          </div>
          <div className="space-y-2">
            <Label htmlFor="password">{t("auth.register.password")}</Label>
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
              {t("auth.register.confirmPassword")}
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
        <CardFooter className="flex flex-col gap-4">
          <Button type="submit" className="w-full" disabled={isSubmitting}>
            {isSubmitting ? t("auth.register.creating") : t("auth.register.submit")}
          </Button>
          <p className="text-sm text-center text-muted-foreground">
            {t("auth.register.hasAccount")}{" "}
            <Link href="/auth/login" className="text-primary hover:underline">
              {t("auth.register.signIn")}
            </Link>
          </p>
        </CardFooter>
      </form>
    </Card>
  );
}

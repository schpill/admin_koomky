"use client";

import Link from "next/link";
import { useRouter } from "next/navigation";
import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import { z } from "zod";
import { useMemo, useState } from "react";
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
import { Loader2 } from "lucide-react";
import { useI18n } from "@/components/providers/i18n-provider";

type LoginFormData = {
  email: string;
  password: string;
};

type TwoFactorFormData = {
  code: string;
};

export default function LoginPage() {
  const router = useRouter();
  const setAuth = useAuthStore((state) => state.setAuth);
  const setTokens = useAuthStore((state) => state.setTokens);
  const { t } = useI18n();

  const [requires2fa, setRequires2fa] = useState(false);
  const [loading, setLoading] = useState(false);

  const loginSchema = useMemo(
    () =>
      z.object({
        email: z.string().email(t("auth.validation.invalidEmail")),
        password: z.string().min(1, t("auth.validation.requiredPassword")),
      }),
    [t]
  );

  const twoFactorSchema = useMemo(
    () =>
      z.object({
        code: z.string().length(6, t("auth.validation.codeLength")),
      }),
    [t]
  );

  const loginForm = useForm<LoginFormData>({
    resolver: zodResolver(loginSchema),
  });

  const twoFactorForm = useForm<TwoFactorFormData>({
    resolver: zodResolver(twoFactorSchema),
  });

  const onLoginSubmit = async (data: LoginFormData) => {
    setLoading(true);
    try {
      const response = await apiClient.post<any>("/auth/login", data, {
        skipAuth: true,
      });

      if (response.data.requires_2fa) {
        setRequires2fa(true);
        // Set the temporary token so subsequent verify call works
        setTokens(response.data.access_token, "");
        toast.info(t("auth.login.toasts.enter2fa"));
      } else {
        setAuth(
          response.data.user,
          response.data.access_token,
          response.data.refresh_token
        );
        toast.success(t("auth.login.toasts.welcome"));
        router.push("/");
      }
    } catch (error: any) {
      toast.error(error.message || t("auth.login.toasts.failed"));
    } finally {
      setLoading(false);
    }
  };

  const on2faSubmit = async (data: TwoFactorFormData) => {
    setLoading(true);
    try {
      const response = await apiClient.post<any>("/auth/2fa/verify", data);
      setAuth(
        response.data.user,
        response.data.access_token,
        response.data.refresh_token
      );
      toast.success(t("auth.twoFactor.verified"));
      router.push("/");
    } catch (error: any) {
      toast.error(error.message || t("auth.twoFactor.failed"));
    } finally {
      setLoading(false);
    }
  };

  if (requires2fa) {
    return (
      <Card className="w-full max-w-md mx-auto">
        <CardHeader className="space-y-1">
          <CardTitle className="text-2xl font-bold text-center">
            {t("auth.twoFactor.title")}
          </CardTitle>
          <CardDescription className="text-center">
            {t("auth.twoFactor.description")}
          </CardDescription>
        </CardHeader>
        <form onSubmit={twoFactorForm.handleSubmit(on2faSubmit)}>
          <CardContent className="space-y-4">
            <div className="space-y-2">
              <Label htmlFor="code">{t("auth.twoFactor.codeLabel")}</Label>
              <Input
                id="code"
                placeholder={t("auth.twoFactor.codePlaceholder")}
                {...twoFactorForm.register("code")}
                disabled={loading}
                autoFocus
              />
              {twoFactorForm.formState.errors.code && (
                <p className="text-sm text-destructive">
                  {twoFactorForm.formState.errors.code.message}
                </p>
              )}
            </div>
          </CardContent>
          <CardFooter>
            <Button type="submit" className="w-full" disabled={loading}>
              {loading && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
              {t("auth.twoFactor.verify")}
            </Button>
          </CardFooter>
        </form>
      </Card>
    );
  }

  return (
    <Card className="w-full max-w-md mx-auto">
      <CardHeader className="space-y-1">
        <CardTitle className="text-2xl font-bold text-center">
          {t("auth.login.title")}
        </CardTitle>
        <CardDescription className="text-center">
          {t("auth.login.description")}
        </CardDescription>
      </CardHeader>
      <form onSubmit={loginForm.handleSubmit(onLoginSubmit)}>
        <CardContent className="space-y-4">
          <div className="space-y-2">
            <Label htmlFor="email">{t("auth.login.email")}</Label>
            <Input
              id="email"
              type="email"
              placeholder={t("auth.login.emailPlaceholder")}
              {...loginForm.register("email")}
              disabled={loading}
            />
            {loginForm.formState.errors.email && (
              <p className="text-sm text-destructive">
                {loginForm.formState.errors.email.message}
              </p>
            )}
          </div>
          <div className="space-y-2">
            <div className="flex items-center justify-between">
              <Label htmlFor="password">{t("auth.login.password")}</Label>
              <Link
                href="/auth/forgot-password"
                className="text-sm text-muted-foreground hover:text-primary"
              >
                {t("auth.login.forgotPassword")}
              </Link>
            </div>
            <Input
              id="password"
              type="password"
              {...loginForm.register("password")}
              disabled={loading}
            />
            {loginForm.formState.errors.password && (
              <p className="text-sm text-destructive">
                {loginForm.formState.errors.password.message}
              </p>
            )}
          </div>
        </CardContent>
        <CardFooter>
          <Button type="submit" className="w-full" disabled={loading}>
            {loading && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
            {t("auth.login.submit")}
          </Button>
        </CardFooter>
      </form>
    </Card>
  );
}

"use client";

import { useState, useEffect } from "react";
import Image from "next/image";
import { Button } from "@/components/ui/button";
import {
  Card,
  CardContent,
  CardDescription,
  CardFooter,
  CardHeader,
  CardTitle,
} from "@/components/ui/card";
import { DashboardLayout } from "@/components/layout/dashboard-layout";
import { toast } from "sonner";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import { z } from "zod";
import { useMemo } from "react";
import { apiClient } from "@/lib/api";
import { useAuthStore } from "@/lib/stores/auth";
import { useI18n } from "@/components/providers/i18n-provider";

type TwoFaFormData = {
  code: string;
};

export default function SecuritySettingsPage() {
  const { user, setUser } = useAuthStore();
  const { t } = useI18n();
  const [qrCode, setQrCode] = useState<string | null>(null);
  const [setupStep, setSetupStep] = useState<"initial" | "verify">("initial");

  const isEnabled = !!user?.two_factor_confirmed_at;

  const twoFaSchema = useMemo(
    () =>
      z.object({
        code: z.string().length(6, t("auth.validation.codeLength")),
      }),
    [t],
  );

  const {
    register,
    handleSubmit,
    formState: { errors, isSubmitting },
    reset,
  } = useForm<TwoFaFormData>({
    resolver: zodResolver(twoFaSchema),
  });

  const onEnable = async () => {
    try {
      const response = await apiClient.post<any>("/settings/2fa/enable");
      setQrCode(response.data.qr_code_url);
      setSetupStep("verify");
    } catch (error) {
      toast.error(t("settings.security.toasts.generateQrFailed"));
    }
  };

  const onVerify = async (data: TwoFaFormData) => {
    try {
      await apiClient.post("/settings/2fa/confirm", data);

      // Refresh user profile to get updated 2FA status
      const userRes = await apiClient.get<any>("/settings/profile");
      setUser(userRes.data);

      setSetupStep("initial");
      setQrCode(null);
      reset();
      toast.success(t("settings.security.toasts.enabled"));
    } catch (error) {
      toast.error(t("settings.security.toasts.verifyFailed"));
    }
  };

  const onDisable = async () => {
    try {
      await apiClient.post("/settings/2fa/disable");

      // Refresh user profile
      const userRes = await apiClient.get<any>("/settings/profile");
      setUser(userRes.data);

      toast.success(t("settings.security.toasts.disabled"));
    } catch (error) {
      toast.error(t("settings.security.toasts.disableFailed"));
    }
  };

  return (
    <DashboardLayout>
      <div className="max-w-2xl mx-auto space-y-6">
        <h1 className="text-3xl font-bold">{t("settings.security.title")}</h1>

        <Card>
          <CardHeader>
            <CardTitle>{t("settings.security.cardTitle")}</CardTitle>
            <CardDescription>
              {t("settings.security.cardDescription")}
            </CardDescription>
          </CardHeader>
          <CardContent className="space-y-4">
            {isEnabled ? (
              <div className="flex items-center justify-between rounded-lg border p-4 bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-900">
                <div className="space-y-0.5">
                  <h3 className="font-medium text-base">
                    {t("settings.security.enabledTitle")}
                  </h3>
                  <p className="text-sm text-muted-foreground">
                    {t("settings.security.enabledDescription")}
                  </p>
                </div>
                <Button variant="destructive" onClick={onDisable}>
                  {t("settings.security.disable")}
                </Button>
              </div>
            ) : (
              <div className="space-y-4">
                {setupStep === "initial" && (
                  <div className="flex items-center justify-between">
                    <p className="text-sm text-muted-foreground">
                      {t("settings.security.disabledDescription")}
                    </p>
                    <Button onClick={onEnable}>
                      {t("settings.security.enable")}
                    </Button>
                  </div>
                )}

                {setupStep === "verify" && qrCode && (
                  <div className="space-y-6 animate-in fade-in slide-in-from-top-4">
                    <div className="flex flex-col items-center space-y-4">
                      <div className="bg-white p-2 rounded-lg border">
                        <Image
                          src={qrCode}
                          alt={t("settings.security.qrAlt")}
                          width={192}
                          height={192}
                          className="h-48 w-48"
                          unoptimized
                        />
                      </div>
                      <p className="text-sm text-center text-muted-foreground max-w-sm">
                        {t("settings.security.qrHelp")}
                      </p>
                    </div>

                    <form
                      onSubmit={handleSubmit(onVerify)}
                      className="space-y-4 max-w-xs mx-auto"
                    >
                      <div className="space-y-2">
                        <Label htmlFor="code">
                          {t("auth.twoFactor.codeLabel")}
                        </Label>
                        <Input
                          id="code"
                          placeholder={t("auth.twoFactor.codePlaceholder")}
                          className="text-center text-lg tracking-widest"
                          maxLength={6}
                          {...register("code")}
                          disabled={isSubmitting}
                        />
                        {errors.code && (
                          <p className="text-sm text-destructive text-center">
                            {errors.code.message}
                          </p>
                        )}
                      </div>
                      <div className="flex gap-2">
                        <Button
                          type="button"
                          variant="ghost"
                          className="flex-1"
                          onClick={() => {
                            setSetupStep("initial");
                            setQrCode(null);
                          }}
                        >
                          {t("common.cancel")}
                        </Button>
                        <Button
                          type="submit"
                          className="flex-1"
                          disabled={isSubmitting}
                        >
                          {isSubmitting
                            ? t("settings.security.verifying")
                            : t("auth.twoFactor.verify")}
                        </Button>
                      </div>
                    </form>
                  </div>
                )}
              </div>
            )}
          </CardContent>
        </Card>
      </div>
    </DashboardLayout>
  );
}

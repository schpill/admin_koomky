"use client";

import { useState } from "react";
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

const twoFaSchema = z.object({
  code: z.string().length(6, "Code must be 6 digits"),
});

type TwoFaFormData = z.infer<typeof twoFaSchema>;

export default function SecuritySettingsPage() {
  const [isEnabled, setIsEnabled] = useState(false);
  const [qrCode, setQrCode] = useState<string | null>(null);
  const [setupStep, setSetupStep] = useState<"initial" | "verify">("initial");

  const {
    register,
    handleSubmit,
    formState: { errors, isSubmitting },
    reset,
  } = useForm<TwoFaFormData>({
    resolver: zodResolver(twoFaSchema),
  });

  const onEnable = async () => {
    // Mock API call to get QR code
    // In real app: const res = await apiClient.post('/settings/2fa/enable');
    // setQrCode(res.data.qr_code_url);
    toast.info("Backend 2FA support missing (Package install failed). Mocking flow.");
    setQrCode("https://upload.wikimedia.org/wikipedia/commons/d/d0/QR_code_for_mobile_English_Wikipedia.svg"); // Placeholder
    setSetupStep("verify");
  };

  const onVerify = async (data: TwoFaFormData) => {
    try {
      // Mock API call to verify
      // await apiClient.post('/settings/2fa/confirm', data);
      await new Promise(resolve => setTimeout(resolve, 1000));
      setIsEnabled(true);
      setSetupStep("initial");
      setQrCode(null);
      reset();
      toast.success("Two-factor authentication enabled!");
    } catch (error) {
      toast.error("Verification failed");
    }
  };

  const onDisable = async () => {
    // Mock API call to disable
    setIsEnabled(false);
    toast.success("Two-factor authentication disabled");
  };

  return (
    <DashboardLayout>
      <div className="max-w-2xl mx-auto space-y-6">
        <h1 className="text-3xl font-bold">Security</h1>

        <Card>
          <CardHeader>
            <CardTitle>Two-Factor Authentication</CardTitle>
            <CardDescription>
              Add an extra layer of security to your account by requiring a verification code when signing in.
            </CardDescription>
          </CardHeader>
          <CardContent className="space-y-4">
            {isEnabled ? (
              <div className="flex items-center justify-between rounded-lg border p-4 bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-900">
                <div className="space-y-0.5">
                  <h3 className="font-medium text-base">2FA is enabled</h3>
                  <p className="text-sm text-muted-foreground">
                    Your account is secure.
                  </p>
                </div>
                <Button variant="destructive" onClick={onDisable}>Disable</Button>
              </div>
            ) : (
              <div className="space-y-4">
                {setupStep === "initial" && (
                  <div className="flex items-center justify-between">
                    <p className="text-sm text-muted-foreground">
                      2FA is currently disabled.
                    </p>
                    <Button onClick={onEnable}>Enable 2FA</Button>
                  </div>
                )}

                {setupStep === "verify" && qrCode && (
                  <div className="space-y-6 animate-in fade-in slide-in-from-top-4">
                    <div className="flex flex-col items-center space-y-4">
                      <div className="bg-white p-2 rounded-lg border">
                        <img src={qrCode} alt="QR Code" className="w-48 h-48" />
                      </div>
                      <p className="text-sm text-center text-muted-foreground max-w-sm">
                        Scan this QR code with your authenticator app (e.g. Google Authenticator) and enter the 6-digit code below.
                      </p>
                    </div>

                    <form onSubmit={handleSubmit(onVerify)} className="space-y-4 max-w-xs mx-auto">
                      <div className="space-y-2">
                        <Label htmlFor="code">Verification Code</Label>
                        <Input
                          id="code"
                          placeholder="000000"
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
                          Cancel
                        </Button>
                        <Button type="submit" className="flex-1" disabled={isSubmitting}>
                          {isSubmitting ? "Verifying..." : "Verify"}
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

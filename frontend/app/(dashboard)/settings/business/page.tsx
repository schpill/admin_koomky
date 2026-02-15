"use client";

import { useEffect } from "react";
import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import { z } from "zod";
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

const businessSchema = z.object({
  business_name: z
    .string()
    .min(2, "Business name must be at least 2 characters"),
});

type BusinessFormData = z.infer<typeof businessSchema>;

export default function BusinessSettingsPage() {
  const { user, setUser } = useAuthStore();

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
      toast.success("Business settings updated successfully");
      reset(data);
    } catch (error) {
      toast.error(error instanceof Error ? error.message : "Update failed");
    }
  };

  return (
    <DashboardLayout>
      <div className="max-w-2xl mx-auto space-y-6">
        <h1 className="text-3xl font-bold">Settings</h1>

        <Card>
          <CardHeader>
            <CardTitle>Business Settings</CardTitle>
            <CardDescription>
              Manage your business profile and information used for invoices and
              clients.
            </CardDescription>
          </CardHeader>
          <form onSubmit={handleSubmit(onSubmit)}>
            <CardContent className="space-y-4">
              <div className="space-y-2">
                <Label htmlFor="business_name">Business Name</Label>
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
                {isSubmitting ? "Saving..." : "Save changes"}
              </Button>
            </CardFooter>
          </form>
        </Card>
      </div>
    </DashboardLayout>
  );
}

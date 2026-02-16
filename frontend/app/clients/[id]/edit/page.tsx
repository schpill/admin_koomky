"use client";

import { useEffect, useState } from "react";
import { useRouter, useParams } from "next/navigation";
import { ClientForm, ClientFormData } from "@/components/clients/client-form";
import { useClientStore } from "@/lib/stores/clients";
import { toast } from "sonner";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Loader2 } from "lucide-react";
import { useI18n } from "@/components/providers/i18n-provider";

export default function EditClientPage() {
  const router = useRouter();
  const params = useParams();
  const id = params.id as string;
  const { t } = useI18n();

  const { fetchClient, updateClient, currentClient } = useClientStore();
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const loadClient = async () => {
      try {
        await fetchClient(id);
      } catch (error) {
        toast.error(t("clients.detail.toasts.loadFailed"));
        router.push("/clients");
      } finally {
        setLoading(false);
      }
    };
    loadClient();
  }, [id, fetchClient, router, t]);

  const onSubmit = async (data: ClientFormData) => {
    try {
      await updateClient(id, data);
      toast.success(t("clients.edit.toasts.updated"));
      router.push(`/clients/${id}`);
    } catch (error) {
      toast.error(t("clients.edit.toasts.updateFailed"));
    }
  };

  if (loading) {
    return (
      <div className="flex h-[400px] items-center justify-center">
        <Loader2 className="h-8 w-8 animate-spin text-primary" />
      </div>
    );
  }

  if (!currentClient) {
    return (
      <div className="container py-8">
        <p>{t("clients.edit.notFound")}</p>
      </div>
    );
  }

  return (
    <div className="container max-w-2xl py-8">
      <Card>
        <CardHeader>
          <CardTitle className="text-2xl font-bold">
            {t("clients.edit.title")}
          </CardTitle>
        </CardHeader>
        <CardContent>
          <ClientForm
            initialData={currentClient}
            onSubmit={onSubmit}
            onCancel={() => router.back()}
            submitLabel={t("clients.edit.updateClient")}
          />
        </CardContent>
      </Card>
    </div>
  );
}

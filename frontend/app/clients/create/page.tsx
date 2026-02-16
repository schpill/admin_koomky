"use client";

import { useRouter } from "next/navigation";
import { ClientForm, ClientFormData } from "@/components/clients/client-form";
import { useClientStore } from "@/lib/stores/clients";
import { toast } from "sonner";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { useI18n } from "@/components/providers/i18n-provider";

export default function CreateClientPage() {
  const router = useRouter();
  const createClient = useClientStore((state) => state.createClient);
  const { t } = useI18n();

  const onSubmit = async (data: ClientFormData) => {
    try {
      await createClient(data);
      toast.success(t("clients.createDialog.toasts.success"));
      router.push("/clients");
    } catch (error) {
      toast.error(t("clients.createDialog.toasts.failed"));
    }
  };

  return (
    <div className="container max-w-2xl py-8">
      <Card>
        <CardHeader>
          <CardTitle className="text-2xl font-bold">
            {t("clients.createDialog.addNewClient")}
          </CardTitle>
        </CardHeader>
        <CardContent>
          <ClientForm
            onSubmit={onSubmit}
            onCancel={() => router.back()}
            submitLabel={t("clients.createDialog.createClient")}
          />
        </CardContent>
      </Card>
    </div>
  );
}

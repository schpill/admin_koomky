"use client";

import { useRouter } from "next/navigation";
import { ClientForm, ClientFormData } from "@/components/clients/client-form";
import { useClientStore } from "@/lib/stores/clients";
import { toast } from "sonner";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";

export default function CreateClientPage() {
  const router = useRouter();
  const createClient = useClientStore((state) => state.createClient);

  const onSubmit = async (data: ClientFormData) => {
    try {
      await createClient(data);
      toast.success("Client created successfully");
      router.push("/clients");
    } catch (error) {
      toast.error("Failed to create client");
    }
  };

  return (
    <div className="container max-w-2xl py-8">
      <Card>
        <CardHeader>
          <CardTitle className="text-2xl font-bold">Add New Client</CardTitle>
        </CardHeader>
        <CardContent>
          <ClientForm
            onSubmit={onSubmit}
            onCancel={() => router.back()}
            submitLabel="Create Client"
          />
        </CardContent>
      </Card>
    </div>
  );
}

"use client";

import { useEffect, useState } from "react";
import { useRouter, useParams } from "next/navigation";
import { ClientForm, ClientFormData } from "@/components/clients/client-form";
import { useClientStore } from "@/lib/stores/clients";
import { toast } from "sonner";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Loader2 } from "lucide-react";

export default function EditClientPage() {
  const router = useRouter();
  const params = useParams();
  const id = params.id as string;
  
  const { fetchClient, updateClient, currentClient } = useClientStore();
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const loadClient = async () => {
      try {
        await fetchClient(id);
      } catch (error) {
        toast.error("Failed to load client");
        router.push("/clients");
      } finally {
        setLoading(false);
      }
    };
    loadClient();
  }, [id, fetchClient, router]);

  const onSubmit = async (data: ClientFormData) => {
    try {
      await updateClient(id, data);
      toast.success("Client updated successfully");
      router.push(`/clients/${id}`);
    } catch (error) {
      toast.error("Failed to update client");
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
        <p>Client not found</p>
      </div>
    );
  }

  return (
    <div className="container max-w-2xl py-8">
      <Card>
        <CardHeader>
          <CardTitle className="text-2xl font-bold">Edit Client</CardTitle>
        </CardHeader>
        <CardContent>
          <ClientForm
            initialData={currentClient}
            onSubmit={onSubmit}
            onCancel={() => router.back()}
            submitLabel="Update Client"
          />
        </CardContent>
      </Card>
    </div>
  );
}

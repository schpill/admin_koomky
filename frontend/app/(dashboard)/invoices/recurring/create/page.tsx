"use client";

import Link from "next/link";
import { useEffect } from "react";
import { useRouter } from "next/navigation";
import { ChevronLeft } from "lucide-react";
import { toast } from "sonner";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { RecurringInvoiceForm } from "@/components/invoices/recurring-invoice-form";
import {
  useRecurringInvoiceStore,
  type RecurringInvoiceProfilePayload,
} from "@/lib/stores/recurring-invoices";
import { useClientStore } from "@/lib/stores/clients";

export default function CreateRecurringInvoicePage() {
  const router = useRouter();
  const { clients, fetchClients } = useClientStore();
  const { createProfile, isLoading } = useRecurringInvoiceStore();

  useEffect(() => {
    fetchClients();
  }, [fetchClients]);

  const handleSubmit = async (payload: RecurringInvoiceProfilePayload) => {
    try {
      const created = await createProfile(payload);
      toast.success("Recurring profile created");

      if (created?.id) {
        router.push(`/invoices/recurring/${created.id}`);
      } else {
        router.push("/invoices/recurring");
      }
    } catch (error) {
      toast.error((error as Error).message || "Unable to create recurring profile");
    }
  };

  return (
    <div className="space-y-6">
      <div className="space-y-2">
        <Button asChild className="-ml-2" variant="ghost">
          <Link href="/invoices/recurring">
            <ChevronLeft className="mr-2 h-4 w-4" />
            Back to recurring invoices
          </Link>
        </Button>
        <h1 className="text-3xl font-bold">Create recurring profile</h1>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Profile details</CardTitle>
        </CardHeader>
        <CardContent>
          <RecurringInvoiceForm
            clients={clients.map((client) => ({ id: client.id, name: client.name }))}
            onSubmit={handleSubmit}
            isSubmitting={isLoading}
            submitLabel="Create profile"
            onCancel={() => router.push("/invoices/recurring")}
          />
        </CardContent>
      </Card>
    </div>
  );
}

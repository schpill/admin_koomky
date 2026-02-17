"use client";

import Link from "next/link";
import { useEffect } from "react";
import { useParams, useRouter } from "next/navigation";
import { ChevronLeft } from "lucide-react";
import { toast } from "sonner";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Skeleton } from "@/components/ui/skeleton";
import { RecurringInvoiceForm } from "@/components/invoices/recurring-invoice-form";
import {
  useRecurringInvoiceStore,
  type RecurringInvoiceProfilePayload,
} from "@/lib/stores/recurring-invoices";
import { useClientStore } from "@/lib/stores/clients";

export default function EditRecurringInvoicePage() {
  const params = useParams<{ id: string }>();
  const router = useRouter();
  const profileId = params.id;

  const { clients, fetchClients } = useClientStore();
  const { currentProfile, fetchProfile, updateProfile, isLoading } =
    useRecurringInvoiceStore();

  useEffect(() => {
    fetchClients();
  }, [fetchClients]);

  useEffect(() => {
    if (!profileId) {
      return;
    }

    fetchProfile(profileId).catch((error) => {
      toast.error((error as Error).message || "Unable to load profile");
      router.push("/invoices/recurring");
    });
  }, [fetchProfile, profileId, router]);

  const handleSubmit = async (payload: RecurringInvoiceProfilePayload) => {
    try {
      await updateProfile(profileId, payload);
      toast.success("Recurring profile updated");
      router.push(`/invoices/recurring/${profileId}`);
    } catch (error) {
      toast.error((error as Error).message || "Unable to update profile");
    }
  };

  if (!currentProfile || currentProfile.id !== profileId) {
    return (
      <div className="space-y-4">
        <Skeleton className="h-8 w-64" />
        <Skeleton className="h-48 w-full" />
      </div>
    );
  }

  return (
    <div className="space-y-6">
      <div className="space-y-2">
        <Button asChild className="-ml-2" variant="ghost">
          <Link href={`/invoices/recurring/${profileId}`}>
            <ChevronLeft className="mr-2 h-4 w-4" />
            Back to profile
          </Link>
        </Button>
        <h1 className="text-3xl font-bold">Edit recurring profile</h1>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Profile details</CardTitle>
        </CardHeader>
        <CardContent>
          <RecurringInvoiceForm
            clients={clients.map((client) => ({ id: client.id, name: client.name }))}
            initialPayload={{
              client_id: currentProfile.client_id,
              name: currentProfile.name,
              frequency: currentProfile.frequency,
              start_date: currentProfile.start_date,
              end_date: currentProfile.end_date,
              next_due_date: currentProfile.next_due_date,
              day_of_month: currentProfile.day_of_month,
              line_items: currentProfile.line_items,
              notes: currentProfile.notes,
              payment_terms_days: currentProfile.payment_terms_days,
              tax_rate: currentProfile.tax_rate,
              discount_percent: currentProfile.discount_percent,
              max_occurrences: currentProfile.max_occurrences,
              auto_send: currentProfile.auto_send,
              currency: currentProfile.currency,
            }}
            onSubmit={handleSubmit}
            isSubmitting={isLoading}
            submitLabel="Save changes"
            onCancel={() => router.push(`/invoices/recurring/${profileId}`)}
          />
        </CardContent>
      </Card>
    </div>
  );
}

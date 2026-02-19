"use client";

import Link from "next/link";
import { useEffect } from "react";
import { useParams, useRouter } from "next/navigation";
import { ChevronLeft, Pencil } from "lucide-react";
import { toast } from "sonner";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { EmptyState } from "@/components/ui/empty-state";
import { Skeleton } from "@/components/ui/skeleton";
import { useRecurringInvoiceStore } from "@/lib/stores/recurring-invoices";
import { useI18n } from "@/components/providers/i18n-provider";

export default function RecurringInvoiceDetailPage() {
  const { t } = useI18n();
  const params = useParams<{ id: string }>();
  const router = useRouter();
  const profileId = params.id;

  const {
    currentProfile,
    isLoading,
    fetchProfile,
    pauseProfile,
    resumeProfile,
    cancelProfile,
  } = useRecurringInvoiceStore();

  useEffect(() => {
    if (!profileId) {
      return;
    }

    fetchProfile(profileId).catch((error) => {
      toast.error(
        (error as Error).message ||
          t("invoices.recurring.detail.toasts.loadFailed")
      );
      router.push("/invoices/recurring");
    });
  }, [fetchProfile, profileId, router]);

  const runAction = async (
    action: () => Promise<unknown>,
    successMessage: string
  ) => {
    try {
      await action();
      toast.success(successMessage);
      await fetchProfile(profileId);
    } catch (error) {
      toast.error(
        (error as Error).message ||
          t("invoices.recurring.detail.toasts.actionFailed")
      );
    }
  };

  if (isLoading && !currentProfile) {
    return (
      <div className="space-y-4">
        <Skeleton className="h-8 w-64" />
        <Skeleton className="h-48 w-full" />
      </div>
    );
  }

  if (!currentProfile) {
    return (
      <EmptyState
        title={t("invoices.recurring.detail.notFound")}
        description={t("invoices.recurring.detail.notFoundDescription")}
        action={
          <Button asChild>
            <Link href="/invoices/recurring">
              {t("invoices.recurring.detail.backToRecurring")}
            </Link>
          </Button>
        }
      />
    );
  }

  return (
    <div className="space-y-6">
      <div className="space-y-2">
        <Button variant="ghost" className="-ml-2" asChild>
          <Link href="/invoices/recurring">
            <ChevronLeft className="mr-2 h-4 w-4" />
            {t("invoices.recurring.detail.backToRecurring")}
          </Link>
        </Button>

        <div className="flex flex-wrap items-center justify-between gap-2">
          <div>
            <h1 className="text-3xl font-bold">{currentProfile.name}</h1>
            <p className="text-sm text-muted-foreground">
              {currentProfile.frequency} -{" "}
              {t("invoices.recurring.detail.nextDue")}{" "}
              {currentProfile.next_due_date}
            </p>
          </div>
          <div className="flex gap-2">
            <Button asChild variant="outline">
              <Link href={`/invoices/recurring/${currentProfile.id}/edit`}>
                <Pencil className="mr-2 h-4 w-4" />
                {t("invoices.recurring.detail.edit")}
              </Link>
            </Button>
            {currentProfile.status === "active" && (
              <Button
                variant="outline"
                onClick={() =>
                  runAction(
                    () => pauseProfile(currentProfile.id),
                    t("invoices.recurring.detail.toasts.paused")
                  )
                }
              >
                {t("invoices.recurring.detail.pause")}
              </Button>
            )}
            {currentProfile.status === "paused" && (
              <Button
                variant="outline"
                onClick={() =>
                  runAction(
                    () => resumeProfile(currentProfile.id),
                    t("invoices.recurring.detail.toasts.resumed")
                  )
                }
              >
                {t("invoices.recurring.detail.resume")}
              </Button>
            )}
            {currentProfile.status !== "cancelled" && (
              <Button
                variant="outline"
                onClick={() =>
                  runAction(
                    () => cancelProfile(currentProfile.id),
                    t("invoices.recurring.detail.toasts.cancelled")
                  )
                }
              >
                {t("invoices.recurring.detail.cancel")}
              </Button>
            )}
          </div>
        </div>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>{t("invoices.recurring.detail.profileInfo")}</CardTitle>
        </CardHeader>
        <CardContent className="grid gap-4 md:grid-cols-3">
          <div>
            <p className="text-xs text-muted-foreground">
              {t("invoices.recurring.detail.client")}
            </p>
            <p className="font-medium">
              {currentProfile.client?.name || currentProfile.client_id}
            </p>
          </div>
          <div>
            <p className="text-xs text-muted-foreground">
              {t("invoices.recurring.detail.status")}
            </p>
            <p className="font-medium capitalize">{currentProfile.status}</p>
          </div>
          <div>
            <p className="text-xs text-muted-foreground">
              {t("invoices.recurring.detail.occurrencesGenerated")}
            </p>
            <p className="font-medium">
              {currentProfile.occurrences_generated}
            </p>
          </div>
          <div>
            <p className="text-xs text-muted-foreground">
              {t("invoices.recurring.detail.paymentTerms")}
            </p>
            <p className="font-medium">
              {currentProfile.payment_terms_days}{" "}
              {t("invoices.recurring.detail.days")}
            </p>
          </div>
          <div>
            <p className="text-xs text-muted-foreground">
              {t("invoices.recurring.detail.currency")}
            </p>
            <p className="font-medium">{currentProfile.currency}</p>
          </div>
          <div>
            <p className="text-xs text-muted-foreground">
              {t("invoices.recurring.detail.autoSend")}
            </p>
            <p className="font-medium">
              {currentProfile.auto_send
                ? t("invoices.recurring.detail.yes")
                : t("invoices.recurring.detail.no")}
            </p>
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>
            {t("invoices.recurring.detail.generatedInvoices")}
          </CardTitle>
        </CardHeader>
        <CardContent>
          {(currentProfile.generated_invoices || []).length === 0 ? (
            <p className="text-sm text-muted-foreground">
              {t("invoices.recurring.detail.noGeneratedInvoices")}
            </p>
          ) : (
            <div className="overflow-x-auto">
              <table className="w-full text-sm">
                <thead>
                  <tr className="border-b text-left">
                    <th className="pb-2">
                      {t("invoices.recurring.detail.number")}
                    </th>
                    <th className="pb-2">
                      {t("invoices.recurring.detail.issueDate")}
                    </th>
                    <th className="pb-2">
                      {t("invoices.recurring.detail.dueDate")}
                    </th>
                    <th className="pb-2">
                      {t("invoices.recurring.detail.status")}
                    </th>
                    <th className="pb-2">
                      {t("invoices.recurring.detail.total")}
                    </th>
                  </tr>
                </thead>
                <tbody>
                  {currentProfile.generated_invoices?.map((invoice) => (
                    <tr key={invoice.id} className="border-b last:border-0">
                      <td className="py-2">
                        <Link
                          href={`/invoices/${invoice.id}`}
                          className="font-medium text-primary hover:underline"
                        >
                          {invoice.number}
                        </Link>
                      </td>
                      <td className="py-2">{invoice.issue_date}</td>
                      <td className="py-2">{invoice.due_date}</td>
                      <td className="py-2 capitalize">{invoice.status}</td>
                      <td className="py-2">
                        {Number(invoice.total).toFixed(2)} EUR
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          )}
        </CardContent>
      </Card>
    </div>
  );
}

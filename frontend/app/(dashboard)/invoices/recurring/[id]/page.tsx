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

export default function RecurringInvoiceDetailPage() {
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
      toast.error((error as Error).message || "Unable to load profile");
      router.push("/invoices/recurring");
    });
  }, [fetchProfile, profileId, router]);

  const runAction = async (action: () => Promise<unknown>, successMessage: string) => {
    try {
      await action();
      toast.success(successMessage);
      await fetchProfile(profileId);
    } catch (error) {
      toast.error((error as Error).message || "Action failed");
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
        title="Profile not found"
        description="This recurring profile does not exist or is no longer accessible."
        action={
          <Button asChild>
            <Link href="/invoices/recurring">Back to recurring invoices</Link>
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
            Back to recurring invoices
          </Link>
        </Button>

        <div className="flex flex-wrap items-center justify-between gap-2">
          <div>
            <h1 className="text-3xl font-bold">{currentProfile.name}</h1>
            <p className="text-sm text-muted-foreground">
              {currentProfile.frequency} - Next due {currentProfile.next_due_date}
            </p>
          </div>
          <div className="flex gap-2">
            <Button asChild variant="outline">
              <Link href={`/invoices/recurring/${currentProfile.id}/edit`}>
                <Pencil className="mr-2 h-4 w-4" />
                Edit
              </Link>
            </Button>
            {currentProfile.status === "active" && (
              <Button
                variant="outline"
                onClick={() =>
                  runAction(() => pauseProfile(currentProfile.id), "Profile paused")
                }
              >
                Pause
              </Button>
            )}
            {currentProfile.status === "paused" && (
              <Button
                variant="outline"
                onClick={() =>
                  runAction(() => resumeProfile(currentProfile.id), "Profile resumed")
                }
              >
                Resume
              </Button>
            )}
            {currentProfile.status !== "cancelled" && (
              <Button
                variant="outline"
                onClick={() =>
                  runAction(() => cancelProfile(currentProfile.id), "Profile cancelled")
                }
              >
                Cancel
              </Button>
            )}
          </div>
        </div>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Profile information</CardTitle>
        </CardHeader>
        <CardContent className="grid gap-4 md:grid-cols-3">
          <div>
            <p className="text-xs text-muted-foreground">Client</p>
            <p className="font-medium">
              {currentProfile.client?.name || currentProfile.client_id}
            </p>
          </div>
          <div>
            <p className="text-xs text-muted-foreground">Status</p>
            <p className="font-medium capitalize">{currentProfile.status}</p>
          </div>
          <div>
            <p className="text-xs text-muted-foreground">Occurrences generated</p>
            <p className="font-medium">{currentProfile.occurrences_generated}</p>
          </div>
          <div>
            <p className="text-xs text-muted-foreground">Payment terms</p>
            <p className="font-medium">{currentProfile.payment_terms_days} days</p>
          </div>
          <div>
            <p className="text-xs text-muted-foreground">Currency</p>
            <p className="font-medium">{currentProfile.currency}</p>
          </div>
          <div>
            <p className="text-xs text-muted-foreground">Auto send</p>
            <p className="font-medium">{currentProfile.auto_send ? "Yes" : "No"}</p>
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>Generated invoices</CardTitle>
        </CardHeader>
        <CardContent>
          {(currentProfile.generated_invoices || []).length === 0 ? (
            <p className="text-sm text-muted-foreground">No generated invoices yet.</p>
          ) : (
            <div className="overflow-x-auto">
              <table className="w-full text-sm">
                <thead>
                  <tr className="border-b text-left">
                    <th className="pb-2">Number</th>
                    <th className="pb-2">Issue date</th>
                    <th className="pb-2">Due date</th>
                    <th className="pb-2">Status</th>
                    <th className="pb-2">Total</th>
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
                      <td className="py-2">{Number(invoice.total).toFixed(2)} EUR</td>
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

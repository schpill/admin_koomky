"use client";

import Link from "next/link";
import { useEffect } from "react";
import { Plus } from "lucide-react";
import { toast } from "sonner";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Skeleton } from "@/components/ui/skeleton";
import { EmptyState } from "@/components/ui/empty-state";
import { useRecurringInvoiceStore } from "@/lib/stores/recurring-invoices";

export default function RecurringInvoicesPage() {
  const {
    profiles,
    isLoading,
    pagination,
    fetchProfiles,
    pauseProfile,
    resumeProfile,
    cancelProfile,
  } = useRecurringInvoiceStore();

  useEffect(() => {
    fetchProfiles();
  }, [fetchProfiles]);

  const runAction = async (action: () => Promise<unknown>, message: string) => {
    try {
      await action();
      toast.success(message);
    } catch (error) {
      toast.error((error as Error).message || "Action failed");
    }
  };

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between gap-3">
        <div>
          <h1 className="text-3xl font-bold">Recurring invoices</h1>
          <p className="text-sm text-muted-foreground">
            {pagination ? `${pagination.total} profiles` : ""}
          </p>
        </div>
        <Button asChild>
          <Link href="/invoices/recurring/create">
            <Plus className="mr-2 h-4 w-4" />
            New profile
          </Link>
        </Button>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Profiles</CardTitle>
        </CardHeader>
        <CardContent>
          {isLoading && profiles.length === 0 ? (
            <div className="space-y-3">
              <Skeleton className="h-12 w-full" />
              <Skeleton className="h-12 w-full" />
            </div>
          ) : profiles.length === 0 ? (
            <EmptyState
              title="No recurring profile"
              description="Create a profile to automate invoice generation."
            />
          ) : (
            <div className="overflow-x-auto">
              <table className="w-full text-sm">
                <thead>
                  <tr className="border-b text-left">
                    <th className="pb-2">Name</th>
                    <th className="pb-2">Client</th>
                    <th className="pb-2">Frequency</th>
                    <th className="pb-2">Next due</th>
                    <th className="pb-2">Status</th>
                    <th className="pb-2">Occurrences</th>
                    <th className="pb-2 text-right">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  {profiles.map((profile) => (
                    <tr key={profile.id} className="border-b last:border-0">
                      <td className="py-3">
                        <Link
                          href={`/invoices/recurring/${profile.id}`}
                          className="font-medium text-primary hover:underline"
                        >
                          {profile.name}
                        </Link>
                      </td>
                      <td className="py-3">{profile.client?.name || profile.client_id}</td>
                      <td className="py-3 capitalize">{profile.frequency}</td>
                      <td className="py-3">{profile.next_due_date}</td>
                      <td className="py-3 capitalize">{profile.status}</td>
                      <td className="py-3">{profile.occurrences_generated}</td>
                      <td className="py-3">
                        <div className="flex justify-end gap-2">
                          {profile.status === "active" && (
                            <Button
                              size="sm"
                              variant="outline"
                              onClick={() =>
                                runAction(
                                  () => pauseProfile(profile.id),
                                  "Profile paused"
                                )
                              }
                            >
                              Pause
                            </Button>
                          )}
                          {profile.status === "paused" && (
                            <Button
                              size="sm"
                              variant="outline"
                              onClick={() =>
                                runAction(
                                  () => resumeProfile(profile.id),
                                  "Profile resumed"
                                )
                              }
                            >
                              Resume
                            </Button>
                          )}
                          {profile.status !== "cancelled" && (
                            <Button
                              size="sm"
                              variant="outline"
                              onClick={() =>
                                runAction(
                                  () => cancelProfile(profile.id),
                                  "Profile cancelled"
                                )
                              }
                            >
                              Cancel
                            </Button>
                          )}
                        </div>
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

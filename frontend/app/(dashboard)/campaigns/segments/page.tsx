"use client";

import { useEffect } from "react";
import Link from "next/link";
import { Plus, Filter } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { EmptyState } from "@/components/ui/empty-state";
import { Skeleton } from "@/components/ui/skeleton";
import { useSegmentStore } from "@/lib/stores/segments";

export default function SegmentListPage() {
  const { segments, isLoading, fetchSegments, pagination } = useSegmentStore();

  useEffect(() => {
    fetchSegments().catch(() => undefined);
  }, [fetchSegments]);

  return (
    <div className="space-y-6">
      <div className="flex flex-wrap items-center justify-between gap-3">
        <div>
          <h1 className="text-3xl font-bold">Segments</h1>
          <p className="text-sm text-muted-foreground">
            {pagination?.total || 0} segments total
          </p>
        </div>
        <Button asChild>
          <Link href="/campaigns/segments/create">
            <Plus className="mr-2 h-4 w-4" />
            New segment
          </Link>
        </Button>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Segment list</CardTitle>
        </CardHeader>
        <CardContent>
          {isLoading && segments.length === 0 ? (
            <div className="space-y-3">
              <Skeleton className="h-12 w-full" />
              <Skeleton className="h-12 w-full" />
              <Skeleton className="h-12 w-full" />
            </div>
          ) : segments.length === 0 ? (
            <EmptyState
              icon={<Filter className="h-12 w-12" />}
              title="No segments yet"
              description="Build your first dynamic audience segment."
              action={
                <Button asChild>
                  <Link href="/campaigns/segments/create">Create segment</Link>
                </Button>
              }
            />
          ) : (
            <div className="overflow-x-auto">
              <table className="w-full text-sm">
                <thead>
                  <tr className="border-b text-left text-muted-foreground">
                    <th className="pb-2 font-medium">Name</th>
                    <th className="pb-2 font-medium">Description</th>
                    <th className="pb-2 font-medium">Contacts</th>
                    <th className="pb-2 font-medium">Updated</th>
                    <th className="pb-2 font-medium">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  {segments.map((segment) => (
                    <tr key={segment.id} className="border-b last:border-0">
                      <td className="py-3 font-medium">{segment.name}</td>
                      <td className="py-3 text-muted-foreground">
                        {segment.description || "-"}
                      </td>
                      <td className="py-3">
                        {segment.cached_contact_count ??
                          segment.contact_count ??
                          0}
                      </td>
                      <td className="py-3 text-muted-foreground">
                        {segment.updated_at || "-"}
                      </td>
                      <td className="py-3">
                        <Button variant="outline" size="sm" asChild>
                          <Link href={`/campaigns/segments/${segment.id}/edit`}>
                            Edit
                          </Link>
                        </Button>
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

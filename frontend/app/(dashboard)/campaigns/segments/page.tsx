"use client";

import { useEffect } from "react";
import Link from "next/link";
import { Plus, Filter } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { EmptyState } from "@/components/ui/empty-state";
import { Skeleton } from "@/components/ui/skeleton";
import { useSegmentStore } from "@/lib/stores/segments";
import { useI18n } from "@/components/providers/i18n-provider";

export default function SegmentListPage() {
  const { t } = useI18n();
  const { segments, isLoading, fetchSegments, pagination } = useSegmentStore();

  useEffect(() => {
    fetchSegments().catch(() => undefined);
  }, [fetchSegments]);

  return (
    <div className="space-y-6">
      <div className="flex flex-wrap items-center justify-between gap-3">
        <div>
          <h1 className="text-3xl font-bold">
            {t("campaigns.segments.title")}
          </h1>
          <p className="text-sm text-muted-foreground">
            {t("campaigns.segments.totalCount", {
              count: pagination?.total || 0,
            })}
          </p>
        </div>
        <Button asChild>
          <Link href="/campaigns/segments/create">
            <Plus className="mr-2 h-4 w-4" />
            {t("campaigns.segments.newSegment")}
          </Link>
        </Button>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>{t("campaigns.segments.segmentList")}</CardTitle>
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
              title={t("campaigns.segments.empty.title")}
              description={t("campaigns.segments.empty.description")}
              action={
                <Button asChild>
                  <Link href="/campaigns/segments/create">
                    {t("campaigns.segments.empty.action")}
                  </Link>
                </Button>
              }
            />
          ) : (
            <div className="overflow-x-auto">
              <table className="w-full text-sm">
                <thead>
                  <tr className="border-b text-left text-muted-foreground">
                    <th className="pb-2 font-medium">
                      {t("campaigns.segments.table.name")}
                    </th>
                    <th className="pb-2 font-medium">
                      {t("campaigns.segments.table.description")}
                    </th>
                    <th className="pb-2 font-medium">
                      {t("campaigns.segments.table.contacts")}
                    </th>
                    <th className="pb-2 font-medium">
                      {t("campaigns.segments.table.updated")}
                    </th>
                    <th className="pb-2 font-medium">
                      {t("campaigns.segments.table.actions")}
                    </th>
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
                            {t("campaigns.segments.table.edit")}
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

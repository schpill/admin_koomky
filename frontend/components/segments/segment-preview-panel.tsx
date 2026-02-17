"use client";

import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Skeleton } from "@/components/ui/skeleton";
import type { SegmentPreviewContact } from "@/lib/stores/segments";

interface SegmentPreviewPanelProps {
  contacts: SegmentPreviewContact[];
  totalMatching: number;
  isLoading?: boolean;
}

export function SegmentPreviewPanel({
  contacts,
  totalMatching,
  isLoading = false,
}: SegmentPreviewPanelProps) {
  return (
    <Card>
      <CardHeader>
        <CardTitle>Preview ({totalMatching} contacts)</CardTitle>
      </CardHeader>
      <CardContent>
        {isLoading ? (
          <div className="space-y-2">
            <Skeleton className="h-10 w-full" />
            <Skeleton className="h-10 w-full" />
            <Skeleton className="h-10 w-full" />
          </div>
        ) : contacts.length === 0 ? (
          <p className="text-sm text-muted-foreground">
            No matching contacts for current filters.
          </p>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-sm">
              <thead>
                <tr className="border-b text-left text-muted-foreground">
                  <th className="pb-2 font-medium">Name</th>
                  <th className="pb-2 font-medium">Email</th>
                  <th className="pb-2 font-medium">Phone</th>
                  <th className="pb-2 font-medium">Company</th>
                </tr>
              </thead>
              <tbody>
                {contacts.map((contact) => (
                  <tr key={contact.id} className="border-b last:border-0">
                    <td className="py-2">
                      {contact.first_name} {contact.last_name || ""}
                    </td>
                    <td className="py-2">{contact.email || "-"}</td>
                    <td className="py-2">{contact.phone || "-"}</td>
                    <td className="py-2">{contact.client?.name || "-"}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </CardContent>
    </Card>
  );
}

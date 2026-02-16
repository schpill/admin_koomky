"use client";

import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";

interface OutstandingItem {
  id: string;
  number: string;
  client_name?: string;
  status: string;
  due_date: string;
  aging_days: number;
  aging_bucket: string;
  balance_due: number;
}

interface OutstandingTableProps {
  items: OutstandingItem[];
}

function bucketLabel(bucket: string): string {
  switch (bucket) {
    case "0_30":
      return "0-30 days";
    case "31_60":
      return "31-60 days";
    case "61_90":
      return "61-90 days";
    case "90_plus":
      return "90+ days";
    default:
      return bucket;
  }
}

function bucketClass(bucket: string): string {
  switch (bucket) {
    case "0_30":
      return "bg-amber-200 text-amber-800 hover:bg-amber-200";
    case "31_60":
      return "bg-orange-200 text-orange-800 hover:bg-orange-200";
    case "61_90":
      return "bg-rose-200 text-rose-800 hover:bg-rose-200";
    case "90_plus":
      return "bg-red-200 text-red-800 hover:bg-red-200";
    default:
      return "bg-zinc-200 text-zinc-700 hover:bg-zinc-200";
  }
}

export function OutstandingTable({ items }: OutstandingTableProps) {
  return (
    <Card>
      <CardHeader>
        <CardTitle>Outstanding invoices</CardTitle>
      </CardHeader>
      <CardContent>
        {items.length === 0 ? (
          <p className="text-sm text-muted-foreground">
            No outstanding invoices.
          </p>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-sm">
              <thead>
                <tr className="border-b text-left">
                  <th className="pb-3 font-medium text-muted-foreground">
                    Invoice
                  </th>
                  <th className="pb-3 font-medium text-muted-foreground">
                    Client
                  </th>
                  <th className="pb-3 font-medium text-muted-foreground">
                    Due date
                  </th>
                  <th className="pb-3 font-medium text-muted-foreground">
                    Aging
                  </th>
                  <th className="pb-3 font-medium text-muted-foreground">
                    Balance due
                  </th>
                </tr>
              </thead>
              <tbody>
                {items.map((item) => (
                  <tr key={item.id} className="border-b last:border-0">
                    <td className="py-3 font-medium">{item.number}</td>
                    <td className="py-3 text-muted-foreground">
                      {item.client_name || "-"}
                    </td>
                    <td className="py-3 text-muted-foreground">
                      {item.due_date}
                    </td>
                    <td className="py-3">
                      <Badge className={bucketClass(item.aging_bucket)}>
                        {bucketLabel(item.aging_bucket)}
                      </Badge>
                    </td>
                    <td className="py-3">
                      {Number(item.balance_due).toFixed(2)} EUR
                    </td>
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

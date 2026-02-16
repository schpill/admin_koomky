"use client";

import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";

interface RevenuePoint {
  month: string;
  total: number;
}

interface RevenueChartProps {
  data: RevenuePoint[];
  title?: string;
}

export function RevenueChart({
  data,
  title = "Revenue trend",
}: RevenueChartProps) {
  const maxValue = Math.max(...data.map((point) => point.total), 0);

  return (
    <Card>
      <CardHeader>
        <CardTitle>{title}</CardTitle>
      </CardHeader>
      <CardContent>
        {data.length === 0 ? (
          <p className="text-sm text-muted-foreground">
            No data for this period.
          </p>
        ) : (
          <div className="grid gap-2">
            {data.map((point) => {
              const ratio = maxValue > 0 ? (point.total / maxValue) * 100 : 0;

              return (
                <div key={point.month} className="space-y-1">
                  <div className="flex items-center justify-between text-xs text-muted-foreground">
                    <span>{point.month}</span>
                    <span>{Number(point.total).toFixed(2)} EUR</span>
                  </div>
                  <div className="h-2 overflow-hidden rounded bg-muted">
                    <div
                      className="h-full bg-primary"
                      style={{ width: `${Math.max(2, ratio)}%` }}
                    />
                  </div>
                </div>
              );
            })}
          </div>
        )}
      </CardContent>
    </Card>
  );
}

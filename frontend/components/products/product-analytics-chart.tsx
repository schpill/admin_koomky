"use client";

interface ProductAnalyticsChartProps {
  data: { month: string; revenue: number }[];
  isLoading?: boolean;
  currency?: string;
}

export function ProductAnalyticsChart({
  data,
  isLoading,
  currency = "EUR",
}: ProductAnalyticsChartProps) {
  const formatCurrency = (value: number) => {
    return new Intl.NumberFormat("fr-FR", {
      style: "currency",
      currency,
    }).format(value);
  };

  if (isLoading) {
    return (
      <div className="space-y-2">
        {Array.from({ length: 6 }).map((_, i) => (
          <div key={i} className="h-6 bg-muted rounded animate-pulse" />
        ))}
      </div>
    );
  }

  if (!data || data.length === 0) {
    return (
      <div className="flex items-center justify-center h-32">
        <p className="text-muted-foreground text-sm">
          Aucune donnée disponible
        </p>
      </div>
    );
  }

  const maxValue = Math.max(...data.map((d) => d.revenue), 0);

  const formatMonth = (month: string) => {
    const date = new Date(month + "-01");
    return date.toLocaleDateString("fr-FR", {
      year: "2-digit",
      month: "short",
    });
  };

  return (
    <div className="grid gap-2">
      {data.map((point) => {
        const ratio = maxValue > 0 ? (point.revenue / maxValue) * 100 : 0;

        return (
          <div key={point.month} className="space-y-1">
            <div className="flex items-center justify-between text-xs text-muted-foreground">
              <span>{formatMonth(point.month)}</span>
              <span>{formatCurrency(point.revenue)}</span>
            </div>
            <div className="h-2 overflow-hidden rounded bg-muted">
              <div
                className="h-full bg-primary transition-all"
                style={{ width: `${Math.max(ratio > 0 ? 2 : 0, ratio)}%` }}
              />
            </div>
          </div>
        );
      })}
    </div>
  );
}

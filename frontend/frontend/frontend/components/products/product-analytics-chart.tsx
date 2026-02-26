"use client";

import {
  LineChart,
  Line,
  XAxis,
  YAxis,
  CartesianGrid,
  Tooltip,
  ResponsiveContainer,
} from "recharts";

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
      <div className="h-[400px] flex items-center justify-center">
        <div className="text-muted-foreground">Chargement des analytics...</div>
      </div>
    );
  }

  if (!data || data.length === 0) {
    return (
      <div className="h-[400px] flex items-center justify-center">
        <div className="text-muted-foreground">Aucune donnée disponible</div>
      </div>
    );
  }

  return (
    <div className="w-full">
      <ResponsiveContainer width="100%" height={400}>
        <LineChart
          data={data}
          margin={{
            top: 5,
            right: 30,
            left: 20,
            bottom: 5,
          }}
        >
          <CartesianGrid strokeDasharray="3 3" />
          <XAxis
            dataKey="month"
            tick={{ fontSize: 12 }}
            tickFormatter={(value) => {
              const date = new Date(value + "-01");
              return date.toLocaleDateString("fr-FR", {
                year: "2-digit",
                month: "short",
              });
            }}
          />
          <YAxis
            tick={{ fontSize: 12 }}
            tickFormatter={(value) => `€${(value / 1000).toFixed(0)}k`}
          />
          <Tooltip
            formatter={(value: number, name) => [
              formatCurrency(value),
              "Revenus",
            ]}
            labelFormatter={(label) => {
              const date = new Date(label + "-01");
              return date.toLocaleDateString("fr-FR", {
                year: "numeric",
                month: "long",
              });
            }}
            contentStyle={{
              backgroundColor: "hsl(var(--card))",
              border: "1px solid hsl(var(--border))",
              borderRadius: "6px",
            }}
          />
          <Line
            type="monotone"
            dataKey="revenue"
            stroke="hsl(var(--primary))"
            strokeWidth={2}
            dot={{ fill: "hsl(var(--primary))", strokeWidth: 2, r: 4 }}
            activeDot={{ r: 6 }}
          />
        </LineChart>
      </ResponsiveContainer>
    </div>
  );
}

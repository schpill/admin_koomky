"use client";

import { useState } from "react";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { ProductSale } from "@/lib/stores/products";

interface ProductSalesTableProps {
  sales: ProductSale[];
  isLoading?: boolean;
}

type SaleStatus = ProductSale["status"];

const statusConfig: Record<
  SaleStatus,
  { label: string; className: string }
> = {
  pending: { label: "En attente", className: "bg-yellow-100 text-yellow-800" },
  confirmed: { label: "Confirmée", className: "bg-green-100 text-green-800" },
  delivered: { label: "Livrée", className: "bg-blue-100 text-blue-800" },
  cancelled: { label: "Annulée", className: "bg-red-100 text-red-800" },
  refunded: { label: "Remboursée", className: "bg-gray-100 text-gray-800" },
};

export function ProductSalesTable({
  sales,
  isLoading,
}: ProductSalesTableProps) {
  const [statusFilter, setStatusFilter] = useState<SaleStatus | null>(null);

  if (isLoading) {
    return (
      <div className="space-y-4">
        <div className="h-10 bg-muted rounded animate-pulse" />
        <div className="space-y-2">
          {Array.from({ length: 5 }).map((_, i) => (
            <div key={i} className="h-12 bg-muted rounded animate-pulse" />
          ))}
        </div>
      </div>
    );
  }

  const filtered = statusFilter
    ? sales.filter((s) => s.status === statusFilter)
    : sales;

  return (
    <div className="space-y-4">
      {/* Status filters */}
      <div className="flex items-center gap-2 flex-wrap">
        <span className="text-sm text-muted-foreground">Filtrer :</span>
        <Button
          variant={statusFilter === null ? "default" : "outline"}
          size="sm"
          onClick={() => setStatusFilter(null)}
        >
          Tout
        </Button>
        {(Object.entries(statusConfig) as [SaleStatus, (typeof statusConfig)[SaleStatus]][]).map(
          ([status, config]) => (
            <Button
              key={status}
              variant={statusFilter === status ? "default" : "outline"}
              size="sm"
              onClick={() =>
                setStatusFilter(statusFilter === status ? null : status)
              }
            >
              {config.label}
            </Button>
          )
        )}
      </div>

      {/* Table */}
      <div className="rounded-md border overflow-x-auto">
        <table className="w-full text-sm">
          <thead>
            <tr className="border-b bg-muted/40 text-left text-muted-foreground">
              <th className="px-4 py-3 font-medium">Client</th>
              <th className="px-4 py-3 font-medium">Montant</th>
              <th className="px-4 py-3 font-medium">Statut</th>
              <th className="px-4 py-3 font-medium">Date</th>
            </tr>
          </thead>
          <tbody>
            {filtered.length === 0 ? (
              <tr>
                <td
                  colSpan={4}
                  className="px-4 py-8 text-center text-muted-foreground"
                >
                  Aucune vente trouvée.
                </td>
              </tr>
            ) : (
              filtered.map((sale) => (
                <tr key={sale.id} className="border-b last:border-0">
                  <td className="px-4 py-3 font-medium">
                    {sale.client?.name ?? "Client inconnu"}
                  </td>
                  <td className="px-4 py-3">
                    {new Intl.NumberFormat("fr-FR", {
                      style: "currency",
                      currency: sale.currency_code,
                    }).format(sale.total_price)}
                  </td>
                  <td className="px-4 py-3">
                    <Badge className={statusConfig[sale.status].className}>
                      {statusConfig[sale.status].label}
                    </Badge>
                  </td>
                  <td className="px-4 py-3 text-muted-foreground">
                    {sale.sold_at
                      ? new Date(sale.sold_at).toLocaleDateString("fr-FR")
                      : "N/A"}
                  </td>
                </tr>
              ))
            )}
          </tbody>
        </table>
      </div>
    </div>
  );
}

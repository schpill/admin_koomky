"use client";

import { useEffect } from "react";
import { Package, TrendingUp } from "lucide-react";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { useProductsStore } from "@/lib/stores/products";
import { CurrencyAmount } from "@/components/shared/currency-amount";
import Link from "next/link";

export function TopProductsWidget() {
  const { globalAnalytics, isLoading, fetchGlobalAnalytics } = useProductsStore();

  useEffect(() => {
    fetchGlobalAnalytics();
  }, [fetchGlobalAnalytics]);

  const topProducts = globalAnalytics?.top_products || [];
  const hasSales = topProducts.length > 0;

  if (!hasSales && !isLoading) {
    return null;
  }

  return (
    <Card>
      <CardHeader className="flex flex-row items-center justify-between pb-2">
        <CardTitle className="text-sm font-medium">
          Top Produits ce mois
        </CardTitle>
        <Package className="h-4 w-4 text-muted-foreground" />
      </CardHeader>
      <CardContent>
        {isLoading ? (
          <div className="space-y-2">
            {Array.from({ length: 3 }).map((_, i) => (
              <div
                key={i}
                className="h-8 animate-pulse rounded bg-gray-100"
              />
            ))}
          </div>
        ) : !hasSales ? (
          <p className="text-sm text-muted-foreground">
            Aucune vente ce mois
          </p>
        ) : (
          <>
            <div className="space-y-3">
              {topProducts.slice(0, 3).map((product) => (
                <div
                  key={product.id}
                  className="flex items-center justify-between rounded-md border p-2"
                >
                  <div className="flex-1 truncate">
                    <p className="font-medium truncate">{product.name}</p>
                    <p className="text-xs text-muted-foreground">
                      {product.sales_count} vente{product.sales_count > 1 ? 's' : ''}
                    </p>
                  </div>
                  <div className="flex items-center gap-1 text-sm font-medium">
                    <TrendingUp className="h-3 w-3 text-green-500" />
                    <CurrencyAmount
                      amount={product.revenue}
                      currency="EUR"
                    />
                  </div>
                </div>
              ))}
            </div>
            <Link
              href="/products"
              className="mt-3 block text-xs text-muted-foreground hover:underline"
            >
              Voir tous les produits
            </Link>
          </>
        )}
      </CardContent>
    </Card>
  );
}

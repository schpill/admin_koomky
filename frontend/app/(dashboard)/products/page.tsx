import { Suspense } from "react";
import { ProductList } from "@/components/products/product-list";
import { ProductStatsBar } from "@/components/products/product-stats-bar";

export default function ProductsPage() {
  return (
    <div className="flex-1 space-y-4 p-4 md:p-8 pt-6">
      <div className="flex items-center justify-between space-y-2">
        <h2 className="text-3xl font-bold tracking-tight">Catalogue</h2>
      </div>

      <Suspense
        fallback={<div className="h-32 animate-pulse bg-muted rounded-lg" />}
      >
        <ProductStatsBar />
      </Suspense>

      <Suspense
        fallback={<div className="h-96 animate-pulse bg-muted rounded-lg" />}
      >
        <ProductList />
      </Suspense>
    </div>
  );
}

import { Suspense } from "react";
import { ProductDetail } from "@/components/products/product-detail";

interface ProductPageProps {
  params: { id: string };
}

export default async function ProductPage({ params }: ProductPageProps) {
  return (
    <div className="flex-1 space-y-4 p-4 md:p-8 pt-6">
      <Suspense
        fallback={<div className="h-96 animate-pulse bg-muted rounded-lg" />}
      >
        <ProductDetail productId={params.id} />
      </Suspense>
    </div>
  );
}

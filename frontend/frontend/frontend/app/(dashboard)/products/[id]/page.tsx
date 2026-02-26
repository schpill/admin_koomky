import { Suspense } from "react";
import { notFound } from "next/navigation";
import { ProductDetail } from "@/components/products/product-detail";
import { useProductsStore } from "@/stores/products";

interface ProductPageProps {
  params: { id: string };
}

// Server component to handle the initial data
export default async function ProductPage({ params }: ProductPageProps) {
  // In a real app, you'd validate the product exists here
  // For now, we'll let the component handle it

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

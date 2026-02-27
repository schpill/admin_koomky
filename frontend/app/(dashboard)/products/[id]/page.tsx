import { Suspense } from "react";
import { ProductDetail } from "@/components/products/product-detail";

interface ProductPageProps {
  params: Promise<{ id: string }>;
}

export default async function ProductPage({ params }: ProductPageProps) {
  const { id } = await params;

  return (
    <div className="flex-1 space-y-4 p-4 md:p-8 pt-6">
      <Suspense
        fallback={<div className="h-96 animate-pulse bg-muted rounded-lg" />}
      >
        <ProductDetail productId={id} />
      </Suspense>
    </div>
  );
}

import { ProductForm } from "@/components/products/product-form";

export default function NewProductPage() {
  return (
    <div className="flex-1 space-y-4 p-4 md:p-8 pt-6">
      <div className="flex items-center justify-between space-y-2">
        <h2 className="text-3xl font-bold tracking-tight">Nouveau Produit</h2>
      </div>

      <div className="max-w-2xl">
        <ProductForm />
      </div>
    </div>
  );
}

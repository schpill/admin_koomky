"use client";

import { useEffect, useState } from "react";
import { useProductsStore } from "@/lib/stores/products";
import { ProductCard } from "./product-card";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Badge } from "@/components/ui/badge";
import { Plus, Search } from "lucide-react";
import Link from "next/link";

const typeFilters = [
  { value: undefined, label: "Tous" },
  { value: "service" as const, label: "Services" },
  { value: "training" as const, label: "Formations" },
  { value: "product" as const, label: "Produits" },
  { value: "subscription" as const, label: "Abonnements" },
];

export function ProductList() {
  const {
    products,
    filters,
    pagination,
    isLoading,
    fetchProducts,
    setFilters,
    clearFilters,
    deleteProduct,
    restoreProduct,
  } = useProductsStore();

  const [isClient, setIsClient] = useState(false);
  const [searchValue, setSearchValue] = useState(filters.search ?? "");

  useEffect(() => {
    setIsClient(true);
    fetchProducts(1);
  }, [fetchProducts]);

  const handleSearch = (value: string) => {
    setSearchValue(value);
    const timeout = setTimeout(() => {
      setFilters({ search: value || undefined });
    }, 300);
    return () => clearTimeout(timeout);
  };

  const handleTypeFilter = (type: (typeof typeFilters)[number]["value"]) => {
    setFilters({ type });
  };

  const handleActiveFilter = (is_active?: boolean) => {
    setFilters({ is_active });
  };

  if (!isClient || isLoading) {
    return (
      <div className="space-y-4">
        <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
          {Array.from({ length: 6 }).map((_, i) => (
            <div key={i} className="h-48 bg-muted rounded-lg animate-pulse" />
          ))}
        </div>
      </div>
    );
  }

  return (
    <div className="space-y-4">
      {/* Toolbar */}
      <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div className="flex items-center gap-2 flex-1">
          <div className="relative flex-1 max-w-sm">
            <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
            <Input
              placeholder="Rechercher un produit..."
              value={searchValue}
              onChange={(e) => handleSearch(e.target.value)}
              className="pl-9"
            />
          </div>
        </div>

        <Button asChild>
          <Link href="/products/new">
            <Plus className="mr-2 h-4 w-4" />
            Nouveau produit
          </Link>
        </Button>
      </div>

      {/* Type filters */}
      <div className="flex items-center gap-2 flex-wrap">
        {typeFilters.map((f) => (
          <Badge
            key={String(f.value)}
            variant={filters.type === f.value ? "default" : "outline"}
            className="cursor-pointer"
            onClick={() => handleTypeFilter(f.value)}
          >
            {f.label}
          </Badge>
        ))}

        <div className="ml-auto flex gap-2">
          <Badge
            variant={filters.is_active !== false ? "default" : "outline"}
            className="cursor-pointer"
            onClick={() => handleActiveFilter(true)}
          >
            Actifs
          </Badge>
          <Badge
            variant={filters.is_active === false ? "default" : "outline"}
            className="cursor-pointer"
            onClick={() => handleActiveFilter(false)}
          >
            Archivés
          </Badge>
          <Badge
            variant={filters.is_active === undefined ? "default" : "outline"}
            className="cursor-pointer"
            onClick={() => handleActiveFilter(undefined)}
          >
            Tous
          </Badge>
        </div>
      </div>

      {/* Product grid */}
      {products.length === 0 ? (
        <div className="flex flex-col items-center justify-center py-16 text-center">
          <p className="text-muted-foreground mb-4">
            Aucun produit trouvé.
          </p>
          <Button asChild variant="outline">
            <Link href="/products/new">
              <Plus className="mr-2 h-4 w-4" />
              Créer votre premier produit
            </Link>
          </Button>
        </div>
      ) : (
        <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
          {products.map((product) => (
            <ProductCard
              key={product.id}
              product={product}
              onArchive={(id) => deleteProduct(id)}
              onRestore={(id) => restoreProduct(id)}
            />
          ))}
        </div>
      )}

      {/* Pagination */}
      {pagination.last_page > 1 && (
        <div className="flex items-center justify-center gap-2 pt-4">
          <Button
            variant="outline"
            size="sm"
            disabled={pagination.current_page <= 1}
            onClick={() => fetchProducts(pagination.current_page - 1)}
          >
            Précédent
          </Button>
          <span className="text-sm text-muted-foreground">
            Page {pagination.current_page} sur {pagination.last_page}
          </span>
          <Button
            variant="outline"
            size="sm"
            disabled={pagination.current_page >= pagination.last_page}
            onClick={() => fetchProducts(pagination.current_page + 1)}
          >
            Suivant
          </Button>
        </div>
      )}
    </div>
  );
}

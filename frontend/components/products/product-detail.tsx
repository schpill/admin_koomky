"use client";

import { useEffect } from "react";
import { useRouter } from "next/navigation";
import { useProductsStore } from "@/lib/stores/products";
import { ProductTypeBadge } from "./product-type-badge";
import { ProductSalesTable } from "./product-sales-table";
import { ProductAnalyticsChart } from "./product-analytics-chart";
import { CampaignGeneratorDialog } from "./campaign-generator-dialog";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Archive, Edit, RotateCcw, Sparkles } from "lucide-react";
import Link from "next/link";

interface ProductDetailProps {
  productId: string;
}

export function ProductDetail({ productId }: ProductDetailProps) {
  const router = useRouter();
  const {
    selectedProduct,
    productSales,
    salesLoading,
    analytics,
    analyticsLoading,
    isLoading,
    fetchProductSales,
    fetchProductAnalytics,
    deleteProduct,
    restoreProduct,
  } = useProductsStore();

  useEffect(() => {
    fetchProductSales(productId);
    fetchProductAnalytics(productId);
  }, [productId, fetchProductSales, fetchProductAnalytics]);

  if (isLoading || !selectedProduct) {
    return (
      <div className="space-y-4">
        <div className="h-24 bg-muted rounded-lg animate-pulse" />
        <div className="h-96 bg-muted rounded-lg animate-pulse" />
      </div>
    );
  }

  const product = selectedProduct;

  const formatPrice = (price: number) => {
    return new Intl.NumberFormat("fr-FR", {
      style: "currency",
      currency: product.currency_code,
    }).format(price);
  };

  const priceTypeLabels = {
    fixed: "Forfait",
    hourly: "/ heure",
    daily: "/ jour",
    per_unit: "/ unité",
  };

  const handleArchive = async () => {
    await deleteProduct(product.id);
    router.push("/products");
  };

  const handleRestore = async () => {
    await restoreProduct(product.id);
  };

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-start justify-between">
        <div className="space-y-2">
          <div className="flex items-center gap-3">
            <h1 className="text-3xl font-bold">{product.name}</h1>
            <ProductTypeBadge type={product.type} />
            {!product.is_active && (
              <Badge variant="secondary">Archivé</Badge>
            )}
          </div>
          <div className="text-2xl font-semibold text-muted-foreground">
            {formatPrice(product.price)}{" "}
            <span className="text-base">
              {priceTypeLabels[product.price_type]}
            </span>
          </div>
        </div>

        <div className="flex gap-2">
          {product.is_active && (
            <Button asChild variant="outline">
              <Link href={`/products/${product.id}/campaigns/generate`}>
                <Sparkles className="mr-2 h-4 w-4" />
                Campagne IA
              </Link>
            </Button>
          )}
          <Button asChild variant="outline">
            <Link href={`/products/${product.id}/edit`}>
              <Edit className="mr-2 h-4 w-4" />
              Modifier
            </Link>
          </Button>
          {product.is_active ? (
            <Button
              variant="outline"
              className="text-destructive"
              onClick={handleArchive}
            >
              <Archive className="mr-2 h-4 w-4" />
              Archiver
            </Button>
          ) : (
            <Button variant="outline" onClick={handleRestore}>
              <RotateCcw className="mr-2 h-4 w-4" />
              Restaurer
            </Button>
          )}
        </div>
      </div>

      {/* Tabs */}
      <Tabs defaultValue="details">
        <TabsList>
          <TabsTrigger value="details">Détails</TabsTrigger>
          <TabsTrigger value="sales">
            Ventes {product.sales_count ? `(${product.sales_count})` : ""}
          </TabsTrigger>
          <TabsTrigger value="campaigns">
            Campagnes{" "}
            {product.campaigns_count ? `(${product.campaigns_count})` : ""}
          </TabsTrigger>
          <TabsTrigger value="analytics">Analytics</TabsTrigger>
        </TabsList>

        <TabsContent value="details" className="space-y-4 mt-4">
          {product.short_description && (
            <Card>
              <CardContent className="pt-6">
                <p className="text-muted-foreground">
                  {product.short_description}
                </p>
              </CardContent>
            </Card>
          )}

          {product.description && (
            <Card>
              <CardHeader>
                <CardTitle className="text-base">Description</CardTitle>
              </CardHeader>
              <CardContent>
                <p className="whitespace-pre-wrap">{product.description}</p>
              </CardContent>
            </Card>
          )}

          <Card>
            <CardHeader>
              <CardTitle className="text-base">Caractéristiques</CardTitle>
            </CardHeader>
            <CardContent className="space-y-2">
              {product.duration && product.duration_unit && (
                <div className="flex justify-between">
                  <span className="text-muted-foreground">Durée</span>
                  <span>
                    {product.duration}{" "}
                    {{
                      hours: "heure(s)",
                      days: "jour(s)",
                      weeks: "semaine(s)",
                      months: "mois",
                    }[product.duration_unit] ?? product.duration_unit}
                  </span>
                </div>
              )}
              {product.sku && (
                <div className="flex justify-between">
                  <span className="text-muted-foreground">SKU</span>
                  <span className="font-mono">{product.sku}</span>
                </div>
              )}
              <div className="flex justify-between">
                <span className="text-muted-foreground">TVA</span>
                <span>{product.vat_rate}%</span>
              </div>
              {product.tags && product.tags.length > 0 && (
                <div className="flex justify-between items-start">
                  <span className="text-muted-foreground">Tags</span>
                  <div className="flex gap-1 flex-wrap justify-end">
                    {product.tags.map((tag) => (
                      <Badge key={tag} variant="secondary">
                        {tag}
                      </Badge>
                    ))}
                  </div>
                </div>
              )}
            </CardContent>
          </Card>
        </TabsContent>

        <TabsContent value="sales" className="mt-4">
          <ProductSalesTable sales={productSales} isLoading={salesLoading} />
        </TabsContent>

        <TabsContent value="campaigns" className="mt-4">
          <Card>
            <CardContent className="pt-6">
              <div className="flex flex-col items-center justify-center py-8 text-center gap-4">
                <p className="text-muted-foreground">
                  Générez une campagne email IA personnalisée pour ce produit.
                </p>
                <CampaignGeneratorDialog productId={product.id} />
                <Button asChild variant="outline">
                  <Link href={`/products/${product.id}/campaigns/generate`}>
                    <Sparkles className="mr-2 h-4 w-4" />
                    Ouvrir en page complète
                  </Link>
                </Button>
              </div>
            </CardContent>
          </Card>
        </TabsContent>

        <TabsContent value="analytics" className="space-y-4 mt-4">
          {analytics && (
            <>
              <div className="grid gap-4 md:grid-cols-3">
                <Card>
                  <CardHeader className="pb-2">
                    <CardTitle className="text-sm font-medium">
                      CA total
                    </CardTitle>
                  </CardHeader>
                  <CardContent>
                    <div className="text-2xl font-bold">
                      {formatPrice(analytics.total_revenue)}
                    </div>
                  </CardContent>
                </Card>
                <Card>
                  <CardHeader className="pb-2">
                    <CardTitle className="text-sm font-medium">
                      Nb ventes
                    </CardTitle>
                  </CardHeader>
                  <CardContent>
                    <div className="text-2xl font-bold">
                      {analytics.total_sales}
                    </div>
                  </CardContent>
                </Card>
                <Card>
                  <CardHeader className="pb-2">
                    <CardTitle className="text-sm font-medium">
                      Taux de conversion
                    </CardTitle>
                  </CardHeader>
                  <CardContent>
                    <div className="text-2xl font-bold">
                      {(analytics.conversion_rate * 100).toFixed(1)}%
                    </div>
                  </CardContent>
                </Card>
              </div>

              <Card>
                <CardHeader>
                  <CardTitle>Évolution du CA (12 mois)</CardTitle>
                </CardHeader>
                <CardContent>
                  <ProductAnalyticsChart
                    data={analytics.monthly_breakdown}
                    isLoading={analyticsLoading}
                    currency={product.currency_code}
                  />
                </CardContent>
              </Card>
            </>
          )}

          {analyticsLoading && (
            <div className="h-64 bg-muted rounded-lg animate-pulse" />
          )}
        </TabsContent>
      </Tabs>
    </div>
  );
}

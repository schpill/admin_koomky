"use client";

import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader } from "@/components/ui/card";
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";
import { ProductTypeBadge } from "./product-type-badge";
import { Archive, Edit, MoreHorizontal, Sparkles } from "lucide-react";
import Link from "next/link";

interface ProductCardProps {
  product: {
    id: string;
    name: string;
    short_description?: string;
    price: number;
    price_type: "fixed" | "hourly" | "daily" | "per_unit";
    currency_code: string;
    duration?: number;
    duration_unit?: string;
    is_active: boolean;
    type: "service" | "training" | "product" | "subscription";
  };
  onEdit?: (productId: string) => void;
  onArchive?: (productId: string) => void;
  onRestore?: (productId: string) => void;
  showGenerateCampaign?: boolean;
}

export function ProductCard({
  product,
  onEdit,
  onArchive,
  onRestore,
  showGenerateCampaign = true,
}: ProductCardProps) {
  const formatPrice = (price: number) => {
    return new Intl.NumberFormat("fr-FR", {
      style: "currency",
      currency: product.currency_code,
    }).format(price);
  };

  const getDurationLabel = () => {
    if (!product.duration || !product.duration_unit) return null;

    const unitLabels = {
      hours: "heure",
      days: "jour",
      weeks: "semaine",
      months: "mois",
    };

    const unit =
      unitLabels[product.duration_unit as keyof typeof unitLabels] ||
      product.duration_unit;
    return `${product.duration} ${unit}${product.duration > 1 ? "s" : ""}`;
  };

  return (
    <Card
      className={`transition-all ${!product.is_active ? "opacity-60" : ""}`}
    >
      <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
        <div className="space-y-1">
          <h3 className="font-semibold leading-none tracking-tight">
            {product.name}
          </h3>
          <div className="flex items-center gap-2">
            <ProductTypeBadge type={product.type} />
            {!product.is_active && <Badge variant="secondary">Archivé</Badge>}
          </div>
        </div>

        <DropdownMenu>
          <DropdownMenuTrigger asChild>
            <Button variant="ghost" className="h-8 w-8 p-0">
              <MoreHorizontal className="h-4 w-4" />
            </Button>
          </DropdownMenuTrigger>
          <DropdownMenuContent align="end">
            <DropdownMenuItem asChild>
              <Link href={`/products/${product.id}`}>
                <Edit className="mr-2 h-4 w-4" />
                Modifier
              </Link>
            </DropdownMenuItem>

            {showGenerateCampaign && product.is_active && (
              <>
                <DropdownMenuSeparator />
                <DropdownMenuItem asChild>
                  <Link href={`/products/${product.id}/campaigns/generate`}>
                    <Sparkles className="mr-2 h-4 w-4" />
                    Créer une campagne
                  </Link>
                </DropdownMenuItem>
              </>
            )}

            <DropdownMenuSeparator />
            <DropdownMenuItem
              onClick={() =>
                product.is_active
                  ? onArchive?.(product.id)
                  : onRestore?.(product.id)
              }
              className={
                product.is_active ? "text-destructive" : "text-green-600"
              }
            >
              <Archive className="mr-2 h-4 w-4" />
              {product.is_active ? "Archiver" : "Restaurer"}
            </DropdownMenuItem>
          </DropdownMenuContent>
        </DropdownMenu>
      </CardHeader>

      <CardContent>
        <div className="space-y-2">
          {product.short_description && (
            <p className="text-sm text-muted-foreground line-clamp-2">
              {product.short_description}
            </p>
          )}

          <div className="flex items-baseline justify-between">
            <div className="space-y-1">
              <div className="text-2xl font-bold">
                {formatPrice(product.price)}
              </div>
              {getDurationLabel() && (
                <div className="text-sm text-muted-foreground">
                  {getDurationLabel()}
                </div>
              )}
            </div>

            {showGenerateCampaign && product.is_active && (
              <Button asChild size="sm" variant="outline">
                <Link href={`/products/${product.id}/campaigns/generate`}>
                  <Sparkles className="mr-2 h-4 w-4" />
                  Campagne IA
                </Link>
              </Button>
            )}
          </div>
        </div>
      </CardContent>
    </Card>
  );
}

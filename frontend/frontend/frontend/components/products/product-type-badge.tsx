"use client";

import { Badge } from "@/components/ui/badge";

interface ProductTypeBadgeProps {
  type: "service" | "training" | "product" | "subscription";
}

const typeConfig = {
  service: {
    label: "Service",
    className: "bg-blue-100 text-blue-800 hover:bg-blue-200",
  },
  training: {
    label: "Formation",
    className: "bg-purple-100 text-purple-800 hover:bg-purple-200",
  },
  product: {
    label: "Produit",
    className: "bg-green-100 text-green-800 hover:bg-green-200",
  },
  subscription: {
    label: "Abonnement",
    className: "bg-orange-100 text-orange-800 hover:bg-orange-200",
  },
};

export function ProductTypeBadge({ type }: ProductTypeBadgeProps) {
  const config = typeConfig[type];

  return (
    <Badge variant="secondary" className={config.className}>
      {config.label}
    </Badge>
  );
}

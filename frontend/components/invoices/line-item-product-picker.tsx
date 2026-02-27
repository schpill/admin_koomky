"use client";

import { useState, useEffect } from "react";
import { Check, ChevronsUpDown, Package, X } from "lucide-react";
import { cn } from "@/lib/utils";
import { Button } from "@/components/ui/button";
import {
  Command,
  CommandEmpty,
  CommandGroup,
  CommandInput,
  CommandItem,
  CommandList,
} from "@/components/ui/command";
import {
  Popover,
  PopoverContent,
  PopoverTrigger,
} from "@/components/ui/popover";
import { useProductsStore } from "@/lib/stores/products";
import { ProductTypeBadge } from "@/components/products/product-type-badge";

interface Product {
  id: string;
  name: string;
  price: number;
  currency_code: string;
  type: string;
  description: string | null;
  vat_rate: number;
}

interface LineItemProductPickerProps {
  value: string | null;
  onChange: (productId: string | null, productData?: Product) => void;
  disabled?: boolean;
}

export function LineItemProductPicker({
  value,
  onChange,
  disabled = false,
}: LineItemProductPickerProps) {
  const [open, setOpen] = useState(false);
  const { products, fetchProducts } = useProductsStore();

  useEffect(() => {
    // Fetch active products only
    fetchProducts({ isActive: true });
  }, [fetchProducts]);

  const selectedProduct = products.find((p) => String(p.id) === value);

  const handleSelect = (productId: string) => {
    const product = products.find((p) => String(p.id) === productId);
    if (product) {
      onChange(productId, {
        id: String(product.id),
        name: product.name,
        price: Number(product.price),
        currency_code: product.currency_code,
        type: product.type,
        description: product.description ?? null,
        vat_rate: Number(product.vat_rate),
      });
    }
    setOpen(false);
  };

  const handleClear = (e: React.MouseEvent) => {
    e.stopPropagation();
    onChange(null);
  };

  return (
    <Popover open={open} onOpenChange={setOpen}>
      <PopoverTrigger asChild>
        <Button
          variant="outline"
          role="combobox"
          aria-expanded={open}
          className="w-full justify-between"
          disabled={disabled}
        >
          <div className="flex items-center gap-2 truncate">
            <Package className="h-4 w-4 shrink-0 text-muted-foreground" />
            {selectedProduct ? (
              <span className="truncate">{selectedProduct.name}</span>
            ) : (
              <span className="text-muted-foreground">
                Sélectionner un produit...
              </span>
            )}
          </div>
          <div className="flex items-center gap-1">
            {selectedProduct && (
              <span
                role="button"
                tabIndex={0}
                aria-label="Dissocier"
                className="h-4 w-4 shrink-0 opacity-50 hover:opacity-100"
                onClick={handleClear}
                onKeyDown={(e) => {
                  if (e.key === "Enter" || e.key === " ") {
                    e.preventDefault();
                    onChange(null);
                  }
                }}
              >
                <X className="h-3 w-3" />
                <span className="sr-only">Dissocier</span>
              </span>
            )}
            <ChevronsUpDown className="h-4 w-4 shrink-0 opacity-50" />
          </div>
        </Button>
      </PopoverTrigger>
      <PopoverContent className="w-[300px] p-0">
        <Command>
          <CommandInput placeholder="Rechercher un produit..." />
          <CommandList>
            <CommandEmpty>Aucun produit trouvé.</CommandEmpty>
            <CommandGroup>
              {products.map((product) => (
                <CommandItem
                  key={String(product.id)}
                  value={String(product.id)}
                  onSelect={handleSelect}
                  className="flex items-center justify-between"
                >
                  <div className="flex items-center gap-2">
                    <Check
                      className={cn(
                        "h-4 w-4",
                        value === String(product.id)
                          ? "opacity-100"
                          : "opacity-0"
                      )}
                    />
                    <div className="flex flex-col">
                      <span className="text-sm font-medium">
                        {product.name}
                      </span>
                      <div className="flex items-center gap-1">
                        <ProductTypeBadge type={product.type} size="sm" />
                        <span className="text-xs text-muted-foreground">
                          {Number(product.price).toLocaleString("fr-FR", {
                            style: "currency",
                            currency: product.currency_code,
                          })}
                        </span>
                      </div>
                    </div>
                  </div>
                </CommandItem>
              ))}
            </CommandGroup>
          </CommandList>
        </Command>
      </PopoverContent>
    </Popover>
  );
}

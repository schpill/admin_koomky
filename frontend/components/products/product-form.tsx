"use client";

import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import { z } from "zod";
import { useRouter } from "next/navigation";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Textarea } from "@/components/ui/textarea";
import { Label } from "@/components/ui/label";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { useProductsStore, Product } from "@/lib/stores/products";

export const productSchema = z.object({
  name: z.string().min(1, "Le nom est requis").max(255),
  type: z.enum(["service", "training", "product", "subscription"]),
  short_description: z.string().max(500).optional(),
  description: z.string().max(5000).optional(),
  price: z.coerce.number().min(0, "Le prix doit être positif"),
  price_type: z.enum(["fixed", "hourly", "daily", "per_unit"]),
  currency_code: z.string().length(3).default("EUR"),
  vat_rate: z.coerce.number().min(0).max(100).default(20),
  duration: z.coerce.number().int().min(1).optional().nullable(),
  duration_unit: z
    .enum(["hours", "days", "weeks", "months"])
    .optional()
    .nullable(),
  sku: z.string().max(100).optional(),
  is_active: z.boolean().default(true),
});

export type ProductFormValues = z.infer<typeof productSchema>;

interface ProductFormProps {
  defaultValues?: Partial<ProductFormValues>;
  onSubmit?: (data: ProductFormValues) => Promise<void>;
  isLoading?: boolean;
}

export function ProductForm({
  defaultValues,
  onSubmit: onSubmitProp,
  isLoading: isLoadingProp,
}: ProductFormProps) {
  const router = useRouter();
  const { createProduct, isLoading: storeLoading } = useProductsStore();

  const {
    register,
    handleSubmit,
    setValue,
    watch,
    formState: { errors },
  } = useForm<ProductFormValues>({
    resolver: zodResolver(productSchema),
    defaultValues: {
      name: "",
      type: "service",
      price: 0,
      price_type: "fixed",
      currency_code: "EUR",
      vat_rate: 20,
      is_active: true,
      ...defaultValues,
    },
  });

  const isLoading = isLoadingProp ?? storeLoading;
  const typeValue = watch("type");
  const priceTypeValue = watch("price_type");
  const durationUnitValue = watch("duration_unit");

  const handleFormSubmit = async (data: ProductFormValues) => {
    if (onSubmitProp) {
      await onSubmitProp(data);
      return;
    }
    const product = await createProduct(data as Partial<Product>);
    router.push(`/products/${product.id}`);
  };

  return (
    <form onSubmit={handleSubmit(handleFormSubmit)} className="space-y-6">
      <div className="space-y-2">
        <Label htmlFor="name">Nom du produit</Label>
        <Input
          id="name"
          placeholder="Formation Laravel Avancé"
          {...register("name")}
        />
        {errors.name && (
          <p className="text-sm text-destructive">{errors.name.message}</p>
        )}
      </div>

      <div className="space-y-2">
        <Label htmlFor="type">Type</Label>
        <Select
          value={typeValue}
          onValueChange={(v) =>
            setValue("type", v as ProductFormValues["type"])
          }
        >
          <SelectTrigger id="type">
            <SelectValue placeholder="Sélectionner un type" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="service">Service</SelectItem>
            <SelectItem value="training">Formation</SelectItem>
            <SelectItem value="product">Produit</SelectItem>
            <SelectItem value="subscription">Abonnement</SelectItem>
          </SelectContent>
        </Select>
        {errors.type && (
          <p className="text-sm text-destructive">{errors.type.message}</p>
        )}
      </div>

      <div className="space-y-2">
        <Label htmlFor="short_description">Description courte</Label>
        <Input
          id="short_description"
          placeholder="Résumé en une phrase"
          {...register("short_description")}
        />
        {errors.short_description && (
          <p className="text-sm text-destructive">
            {errors.short_description.message}
          </p>
        )}
      </div>

      <div className="space-y-2">
        <Label htmlFor="description">Description complète</Label>
        <Textarea
          id="description"
          placeholder="Description détaillée du produit ou service"
          rows={4}
          {...register("description")}
        />
        {errors.description && (
          <p className="text-sm text-destructive">
            {errors.description.message}
          </p>
        )}
      </div>

      <div className="grid grid-cols-2 gap-4">
        <div className="space-y-2">
          <Label htmlFor="price">Prix</Label>
          <Input
            id="price"
            type="number"
            min="0"
            step="0.01"
            {...register("price")}
          />
          {errors.price && (
            <p className="text-sm text-destructive">{errors.price.message}</p>
          )}
        </div>

        <div className="space-y-2">
          <Label htmlFor="price_type">Type de tarif</Label>
          <Select
            value={priceTypeValue}
            onValueChange={(v) =>
              setValue("price_type", v as ProductFormValues["price_type"])
            }
          >
            <SelectTrigger id="price_type">
              <SelectValue />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="fixed">Forfait</SelectItem>
              <SelectItem value="hourly">Par heure</SelectItem>
              <SelectItem value="daily">Par jour</SelectItem>
              <SelectItem value="per_unit">Par unité</SelectItem>
            </SelectContent>
          </Select>
        </div>
      </div>

      <div className="grid grid-cols-2 gap-4">
        <div className="space-y-2">
          <Label htmlFor="currency_code">Devise</Label>
          <Input
            id="currency_code"
            placeholder="EUR"
            maxLength={3}
            {...register("currency_code")}
          />
        </div>

        <div className="space-y-2">
          <Label htmlFor="vat_rate">TVA (%)</Label>
          <Input
            id="vat_rate"
            type="number"
            min="0"
            max="100"
            step="0.01"
            {...register("vat_rate")}
          />
        </div>
      </div>

      <div className="grid grid-cols-2 gap-4">
        <div className="space-y-2">
          <Label htmlFor="duration">Durée (optionnel)</Label>
          <Input
            id="duration"
            type="number"
            min="1"
            placeholder="Ex: 10"
            {...register("duration", {
              setValueAs: (v) => (v === "" ? null : Number(v)),
            })}
          />
        </div>

        <div className="space-y-2">
          <Label htmlFor="duration_unit">Unité de durée</Label>
          <Select
            value={durationUnitValue ?? ""}
            onValueChange={(v) =>
              setValue(
                "duration_unit",
                (v as ProductFormValues["duration_unit"]) ?? null
              )
            }
          >
            <SelectTrigger id="duration_unit">
              <SelectValue placeholder="Sélectionner" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="hours">Heures</SelectItem>
              <SelectItem value="days">Jours</SelectItem>
              <SelectItem value="weeks">Semaines</SelectItem>
              <SelectItem value="months">Mois</SelectItem>
            </SelectContent>
          </Select>
        </div>
      </div>

      <div className="space-y-2">
        <Label htmlFor="sku">SKU (optionnel)</Label>
        <Input id="sku" placeholder="FORM-LARAVEL-001" {...register("sku")} />
      </div>

      <div className="flex gap-3">
        <Button type="submit" disabled={isLoading}>
          {isLoading ? "Enregistrement..." : "Enregistrer"}
        </Button>
        <Button
          type="button"
          variant="outline"
          onClick={() => router.back()}
          disabled={isLoading}
        >
          Annuler
        </Button>
      </div>
    </form>
  );
}

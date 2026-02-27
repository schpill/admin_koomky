"use client";

import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from "@/components/ui/dialog";
import { Button } from "@/components/ui/button";
import { Sparkles } from "lucide-react";
import { ProductCampaignWizard } from "@/components/products/product-campaign-wizard";

interface CampaignGeneratorDialogProps {
  productId: string;
  open?: boolean;
  onOpenChange?: (open: boolean) => void;
  triggerLabel?: string;
}

export function CampaignGeneratorDialog({
  productId,
  open,
  onOpenChange,
  triggerLabel = "Créer une campagne IA",
}: CampaignGeneratorDialogProps) {
  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      {triggerLabel ? (
        <DialogTrigger asChild>
          <Button>
            <Sparkles className="mr-2 h-4 w-4" />
            {triggerLabel}
          </Button>
        </DialogTrigger>
      ) : null}

      <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-4xl">
        <DialogHeader>
          <DialogTitle>Générer une campagne email IA</DialogTitle>
          <DialogDescription>
            Créez une campagne en brouillon à partir de ce produit avec le
            wizard en 3 étapes.
          </DialogDescription>
        </DialogHeader>

        <ProductCampaignWizard productId={productId} />
      </DialogContent>
    </Dialog>
  );
}

"use client";

import { useRouter } from "next/navigation";
import { useState } from "react";
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
} from "@/components/ui/alert-dialog";
import { ProspectFilters } from "@/lib/stores/prospects";
import { useSegmentStore } from "@/lib/stores/segments";

interface CreateCampaignFromProspectsDialogProps {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  filters: ProspectFilters;
}

export function CreateCampaignFromProspectsDialog({
  open,
  onOpenChange,
  filters,
}: CreateCampaignFromProspectsDialogProps) {
  const router = useRouter();
  const { createSegment } = useSegmentStore();
  const [isSubmitting, setIsSubmitting] = useState(false);

  const handleConfirm = async () => {
    setIsSubmitting(true);
    try {
      const criteria: any[] = [];
      if (filters.industry) {
        criteria.push({
          type: "industry",
          operator: "equals",
          value: filters.industry,
        });
      }
      if (filters.department) {
        criteria.push({
          type: "department",
          operator: "equals",
          value: filters.department,
        });
      }

      const segment = await createSegment({
        name: `Segment prospects ${new Date().toLocaleDateString()}`,
        description: "Segment généré depuis la vue Prospects",
        filters: {
          group_boolean: "and",
          criteria_boolean: "and",
          groups: [{ criteria }],
        },
      });

      if (segment?.id) {
        router.push(`/campaigns/create?segment_id=${segment.id}`);
      }
      onOpenChange(false);
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <AlertDialog open={open} onOpenChange={onOpenChange}>
      <AlertDialogContent>
        <AlertDialogHeader>
          <AlertDialogTitle>Créer une campagne ciblée</AlertDialogTitle>
          <AlertDialogDescription>
            Générer un segment depuis les filtres actifs puis ouvrir la création
            de campagne.
          </AlertDialogDescription>
        </AlertDialogHeader>
        <AlertDialogFooter>
          <AlertDialogCancel>Annuler</AlertDialogCancel>
          <AlertDialogAction disabled={isSubmitting} onClick={handleConfirm}>
            {isSubmitting ? "Création..." : "Confirmer"}
          </AlertDialogAction>
        </AlertDialogFooter>
      </AlertDialogContent>
    </AlertDialog>
  );
}

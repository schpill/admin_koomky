"use client";

import { useState } from "react";
import { useRouter } from "next/navigation";
import { toast } from "sonner";
import { UserPlus, Building2, User, DollarSign, Loader2 } from "lucide-react";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import { Button } from "@/components/ui/button";
import { CurrencyAmount } from "@/components/shared/currency-amount";
import { useLeadStore, Lead } from "@/lib/stores/leads";
import { useI18n } from "@/components/providers/i18n-provider";

interface ConvertToClientDialogProps {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  lead: Lead | null;
  onSuccess?: (clientId: string) => void;
}

export function ConvertToClientDialog({
  open,
  onOpenChange,
  lead,
  onSuccess,
}: ConvertToClientDialogProps) {
  const { t } = useI18n();
  const router = useRouter();
  const { convertToClient, isLoading } = useLeadStore();
  const [isConverting, setIsConverting] = useState(false);

  if (!lead) {
    return null;
  }

  const handleConvert = async () => {
    setIsConverting(true);

    try {
      const result = await convertToClient(lead.id);

      if (result) {
        toast.success(t("leads.convert.toasts.success"), {
          description: t("leads.convert.toasts.successDesc", {
            name: lead.company_name || lead.full_name,
          }),
        });

        onOpenChange(false);
        onSuccess?.(result.client.id);

        // Navigate to the new client page
        router.push(`/clients/${result.client.id}`);
      }
    } catch (error) {
      const message =
        error instanceof Error
          ? error.message
          : t("leads.convert.toasts.errorDesc");
      toast.error(t("leads.convert.toasts.error"), {
        description: message,
      });
    } finally {
      setIsConverting(false);
    }
  };

  const handleCancel = () => {
    if (!isConverting) {
      onOpenChange(false);
    }
  };

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="sm:max-w-md">
        <DialogHeader>
          <DialogTitle className="flex items-center gap-2">
            <UserPlus className="h-5 w-5" />
            {t("leads.convert.title")}
          </DialogTitle>
          <DialogDescription>
            {t("leads.convert.description")}
          </DialogDescription>
        </DialogHeader>

        <div className="space-y-4 py-4">
          <div className="rounded-lg border bg-muted/50 p-4">
            <h4 className="mb-3 text-sm font-medium">
              {t("leads.convert.summary")}
            </h4>
            <div className="space-y-3">
              {lead.company_name && (
                <div className="flex items-center gap-3">
                  <Building2 className="h-4 w-4 text-muted-foreground" />
                  <div>
                    <p className="text-xs text-muted-foreground">
                      {t("leads.convert.company")}
                    </p>
                    <p className="font-medium">{lead.company_name}</p>
                  </div>
                </div>
              )}

              <div className="flex items-center gap-3">
                <User className="h-4 w-4 text-muted-foreground" />
                <div>
                  <p className="text-xs text-muted-foreground">
                    {t("leads.convert.contact")}
                  </p>
                  <p className="font-medium">{lead.full_name}</p>
                </div>
              </div>

              {lead.estimated_value && (
                <div className="flex items-center gap-3">
                  <DollarSign className="h-4 w-4 text-muted-foreground" />
                  <div>
                    <p className="text-xs text-muted-foreground">
                      {t("leads.convert.estimatedValue")}
                    </p>
                    <p className="font-medium">
                      <CurrencyAmount
                        amount={lead.estimated_value}
                        currency={lead.currency}
                      />
                    </p>
                  </div>
                </div>
              )}

              {lead.email && (
                <div className="text-sm">
                  <span className="text-muted-foreground">
                    {t("leads.convert.email")}
                  </span>{" "}
                  <span>{lead.email}</span>
                </div>
              )}

              {lead.phone && (
                <div className="text-sm">
                  <span className="text-muted-foreground">
                    {t("leads.convert.phone")}
                  </span>{" "}
                  <span>{lead.phone}</span>
                </div>
              )}
            </div>
          </div>

          <div className="rounded-lg border border-yellow-200 bg-yellow-50 p-3 text-sm text-yellow-800">
            {t("leads.convert.note")}
          </div>
        </div>

        <DialogFooter className="gap-2 sm:gap-0">
          <Button
            variant="outline"
            onClick={handleCancel}
            disabled={isConverting || isLoading}
          >
            {t("leads.convert.cancel")}
          </Button>
          <Button
            onClick={handleConvert}
            disabled={isConverting || isLoading}
            className="min-w-[120px]"
          >
            {isConverting || isLoading ? (
              <>
                <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                {t("leads.convert.converting")}
              </>
            ) : (
              <>
                <UserPlus className="mr-2 h-4 w-4" />
                {t("leads.convert.confirm")}
              </>
            )}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

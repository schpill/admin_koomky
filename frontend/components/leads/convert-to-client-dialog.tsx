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
        toast.success("Lead converted to client successfully", {
          description: `${lead.company_name || lead.full_name} is now a client.`,
        });

        onOpenChange(false);
        onSuccess?.(result.client.id);

        // Navigate to the new client page
        router.push(`/clients/${result.client.id}`);
      }
    } catch (error) {
      const message =
        error instanceof Error ? error.message : "Failed to convert lead";
      toast.error("Conversion failed", {
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
            Convert Lead to Client
          </DialogTitle>
          <DialogDescription>
            This will create a new client from this lead. The lead will be
            marked as won and linked to the new client record.
          </DialogDescription>
        </DialogHeader>

        <div className="space-y-4 py-4">
          <div className="rounded-lg border bg-muted/50 p-4">
            <h4 className="mb-3 text-sm font-medium">Lead Summary</h4>
            <div className="space-y-3">
              {lead.company_name && (
                <div className="flex items-center gap-3">
                  <Building2 className="h-4 w-4 text-muted-foreground" />
                  <div>
                    <p className="text-xs text-muted-foreground">Company</p>
                    <p className="font-medium">{lead.company_name}</p>
                  </div>
                </div>
              )}

              <div className="flex items-center gap-3">
                <User className="h-4 w-4 text-muted-foreground" />
                <div>
                  <p className="text-xs text-muted-foreground">Contact</p>
                  <p className="font-medium">{lead.full_name}</p>
                </div>
              </div>

              {lead.estimated_value && (
                <div className="flex items-center gap-3">
                  <DollarSign className="h-4 w-4 text-muted-foreground" />
                  <div>
                    <p className="text-xs text-muted-foreground">
                      Estimated Value
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
                  <span className="text-muted-foreground">Email:</span>{" "}
                  <span>{lead.email}</span>
                </div>
              )}

              {lead.phone && (
                <div className="text-sm">
                  <span className="text-muted-foreground">Phone:</span>{" "}
                  <span>{lead.phone}</span>
                </div>
              )}
            </div>
          </div>

          <div className="rounded-lg border border-yellow-200 bg-yellow-50 p-3 text-sm text-yellow-800">
            <strong>Note:</strong> This action will change the lead status to
            &quot;won&quot; and create a new client record. You can edit the
            client details after conversion.
          </div>
        </div>

        <DialogFooter className="gap-2 sm:gap-0">
          <Button
            variant="outline"
            onClick={handleCancel}
            disabled={isConverting || isLoading}
          >
            Cancel
          </Button>
          <Button
            onClick={handleConvert}
            disabled={isConverting || isLoading}
            className="min-w-[120px]"
          >
            {isConverting || isLoading ? (
              <>
                <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                Converting...
              </>
            ) : (
              <>
                <UserPlus className="mr-2 h-4 w-4" />
                Convert to Client
              </>
            )}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

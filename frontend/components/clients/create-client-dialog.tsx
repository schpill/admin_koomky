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
import { useClientStore } from "@/lib/stores/clients";
import { toast } from "sonner";
import { useState } from "react";
import { Plus } from "lucide-react";
import { ClientForm, ClientFormData } from "./client-form";
import { useI18n } from "@/components/providers/i18n-provider";

export function CreateClientDialog() {
  const [open, setOpen] = useState(false);
  const createClient = useClientStore((state) => state.createClient);
  const { t } = useI18n();

  const onSubmit = async (data: ClientFormData) => {
    try {
      await createClient(data);
      toast.success(t("clients.createDialog.toasts.success"));
      setOpen(false);
    } catch (error) {
      toast.error(t("clients.createDialog.toasts.failed"));
    }
  };

  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogTrigger asChild>
        <Button>
          <Plus className="mr-2 h-4 w-4" />{" "}
          {t("clients.createDialog.addClient")}
        </Button>
      </DialogTrigger>
      <DialogContent className="sm:max-w-[500px]">
        <DialogHeader>
          <DialogTitle>{t("clients.createDialog.addNewClient")}</DialogTitle>
          <DialogDescription>
            {t("clients.createDialog.description")}
          </DialogDescription>
        </DialogHeader>
        <div className="py-4">
          <ClientForm
            onSubmit={onSubmit}
            onCancel={() => setOpen(false)}
            submitLabel={t("clients.createDialog.saveClient")}
          />
        </div>
      </DialogContent>
    </Dialog>
  );
}

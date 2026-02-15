"use client";

import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import { z } from "zod";
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

export function CreateClientDialog() {
  const [open, setOpen] = useState(false);
  const createClient = useClientStore((state) => state.createClient);

  const onSubmit = async (data: ClientFormData) => {
    try {
      await createClient(data);
      toast.success("Client created successfully");
      setOpen(false);
    } catch (error) {
      toast.error("Failed to create client");
    }
  };

  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogTrigger asChild>
        <Button>
          <Plus className="mr-2 h-4 w-4" /> Add Client
        </Button>
      </DialogTrigger>
      <DialogContent className="sm:max-w-[500px]">
        <DialogHeader>
          <DialogTitle>Add New Client</DialogTitle>
          <DialogDescription>
            Enter the details of the new client. Click save when you&apos;re done.
          </DialogDescription>
        </DialogHeader>
        <div className="py-4">
          <ClientForm
            onSubmit={onSubmit}
            onCancel={() => setOpen(false)}
            submitLabel="Save Client"
          />
        </div>
      </DialogContent>
    </Dialog>
  );
}

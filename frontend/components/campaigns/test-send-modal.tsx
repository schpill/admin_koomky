"use client";

import { useState } from "react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from "@/components/ui/dialog";

interface TestSendModalProps {
  type: "email" | "sms";
  isSubmitting?: boolean;
  onSend: (payload: { email?: string; phone?: string }) => Promise<void>;
}

export function TestSendModal({
  type,
  isSubmitting = false,
  onSend,
}: TestSendModalProps) {
  const [open, setOpen] = useState(false);
  const [value, setValue] = useState("");

  const submit = async () => {
    if (type === "sms") {
      await onSend({ phone: value });
    } else {
      await onSend({ email: value });
    }

    setOpen(false);
    setValue("");
  };

  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogTrigger asChild>
        <Button type="button" variant="outline">
          Send test
        </Button>
      </DialogTrigger>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Send test {type.toUpperCase()}</DialogTitle>
        </DialogHeader>

        <div className="space-y-3">
          <div className="space-y-2">
            <Label htmlFor="test-destination">
              {type === "sms" ? "Phone number" : "Email address"}
            </Label>
            <Input
              id="test-destination"
              value={value}
              onChange={(event) => setValue(event.target.value)}
              placeholder={type === "sms" ? "+33612345678" : "qa@example.com"}
            />
          </div>

          <Button
            type="button"
            onClick={submit}
            disabled={!value || isSubmitting}
          >
            {isSubmitting ? "Sending..." : "Send"}
          </Button>
        </div>
      </DialogContent>
    </Dialog>
  );
}

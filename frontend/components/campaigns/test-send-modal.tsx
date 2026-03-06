"use client";

import { useMemo, useState } from "react";
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
  onSend: (payload: {
    emails?: string[];
    phones?: string[];
    email?: string;
    phone?: string;
  }) => Promise<void>;
}

function isValidEmail(value: string) {
  return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);
}

export function TestSendModal({
  type,
  isSubmitting = false,
  onSend,
}: TestSendModalProps) {
  const [open, setOpen] = useState(false);
  const [value, setValue] = useState("");
  const [items, setItems] = useState<string[]>([]);
  const [error, setError] = useState<string | null>(null);

  const maxItems = type === "email" ? 5 : 3;

  const canSubmit = useMemo(
    () => items.length > 0 && items.length <= maxItems,
    [items.length, maxItems]
  );

  const addItem = () => {
    const normalized = value.trim();
    if (!normalized) {
      return;
    }

    if (items.includes(normalized)) {
      setError("Valeur déjà ajoutée.");
      return;
    }

    if (items.length >= maxItems) {
      setError(`Maximum ${maxItems} destinataires.`);
      return;
    }

    if (type === "email" && !isValidEmail(normalized)) {
      setError("Format email invalide.");
      return;
    }

    setItems((current) => [...current, normalized]);
    setValue("");
    setError(null);
  };

  const removeItem = (entry: string) => {
    setItems((current) => current.filter((item) => item !== entry));
    setError(null);
  };

  const submit = async () => {
    if (!canSubmit) {
      setError(`Ajoutez entre 1 et ${maxItems} destinataires.`);
      return;
    }

    if (type === "sms") {
      await onSend({ phones: items });
    } else {
      await onSend({ emails: items });
    }

    setOpen(false);
    setValue("");
    setItems([]);
    setError(null);
  };

  const placeholder = type === "sms" ? "+33612345678" : "qa@example.com";

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
              {type === "sms" ? "Numéros (1-3)" : "Emails (1-5)"}
            </Label>
            <div className="flex gap-2">
              <Input
                id="test-destination"
                value={value}
                onChange={(event) => setValue(event.target.value)}
                onKeyDown={(event) => {
                  if (event.key === "Enter") {
                    event.preventDefault();
                    addItem();
                  }
                }}
                placeholder={placeholder}
              />
              <Button type="button" variant="outline" onClick={addItem}>
                Ajouter
              </Button>
            </div>
          </div>

          {type === "email" ? (
            <p className="rounded-md border bg-muted/40 px-3 py-2 text-xs text-muted-foreground">
              Les variables seront résolues avec des données fictives.
            </p>
          ) : null}

          <div className="flex flex-wrap gap-2">
            {items.map((entry) => (
              <button
                type="button"
                key={entry}
                className="rounded-full border px-2 py-1 text-xs"
                onClick={() => removeItem(entry)}
              >
                {entry} ×
              </button>
            ))}
          </div>

          {error ? <p className="text-xs text-red-600">{error}</p> : null}

          <Button type="button" onClick={submit} disabled={!canSubmit || isSubmitting}>
            {isSubmitting ? "Sending..." : "Send"}
          </Button>
        </div>
      </DialogContent>
    </Dialog>
  );
}

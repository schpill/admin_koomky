"use client";

import { useEffect, useMemo, useState } from "react";
import { Keyboard } from "lucide-react";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import { Button } from "@/components/ui/button";

interface KeyboardShortcutsHelpProps {
  open?: boolean;
  onOpenChange?: (open: boolean) => void;
  hideTrigger?: boolean;
}

type Shortcut = {
  key: string;
  description: string;
};

export function KeyboardShortcutsHelp({
  open,
  onOpenChange,
  hideTrigger = false,
}: KeyboardShortcutsHelpProps) {
  const [internalOpen, setInternalOpen] = useState(false);

  const isControlled = typeof open === "boolean";
  const isOpen = isControlled ? Boolean(open) : internalOpen;
  const setOpen = onOpenChange ?? setInternalOpen;

  const shortcuts = useMemo<Shortcut[]>(
    () => [
      { key: "Ctrl/Cmd + K", description: "Open search" },
      { key: "Ctrl/Cmd + N", description: "Create a new record" },
      { key: "Escape", description: "Close current modal or dialog" },
      { key: "?", description: "Open this shortcuts panel" },
    ],
    []
  );

  useEffect(() => {
    const handler = (event: KeyboardEvent) => {
      const target = event.target as HTMLElement | null;
      const isTyping =
        target?.tagName === "INPUT" ||
        target?.tagName === "TEXTAREA" ||
        target?.isContentEditable;
      if (isTyping) {
        return;
      }

      if (event.key === "?" || (event.key === "/" && event.shiftKey)) {
        event.preventDefault();
        setOpen(true);
      }
    };

    window.addEventListener("keydown", handler);
    return () => window.removeEventListener("keydown", handler);
  }, [setOpen]);

  return (
    <>
      {!hideTrigger && (
        <Button
          type="button"
          variant="outline"
          size="sm"
          className="gap-1.5"
          onClick={() => setOpen(true)}
        >
          <Keyboard className="h-4 w-4" />
          Shortcuts
        </Button>
      )}

      <Dialog open={isOpen} onOpenChange={setOpen}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>Keyboard shortcuts</DialogTitle>
            <DialogDescription>
              Main shortcuts available across the application.
            </DialogDescription>
          </DialogHeader>
          <ul className="space-y-3">
            {shortcuts.map((shortcut) => (
              <li
                key={shortcut.key}
                className="flex items-center justify-between gap-4 rounded-md border border-border bg-muted/30 px-3 py-2 text-sm"
              >
                <span className="text-foreground">{shortcut.description}</span>
                <kbd className="rounded border border-border bg-background px-2 py-1 font-mono text-xs text-muted-foreground">
                  {shortcut.key}
                </kbd>
              </li>
            ))}
          </ul>
        </DialogContent>
      </Dialog>
    </>
  );
}

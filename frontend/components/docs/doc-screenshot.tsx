"use client";

import { Dialog, DialogContent } from "@/components/ui/dialog";
import { cn } from "@/lib/utils";
import { useState } from "react";
import { DialogDescription, DialogTitle } from "@/components/ui/dialog";

type DocScreenshotProps = {
  src: string;
  alt: string;
  caption?: string;
  className?: string;
};

export function DocScreenshot({
  src,
  alt,
  caption,
  className,
}: DocScreenshotProps) {
  const [open, setOpen] = useState(false);

  return (
    <>
      <figure className={cn("my-8 space-y-3", className)}>
        <button
          type="button"
          onClick={() => setOpen(true)}
          aria-label={alt}
          className="block w-full overflow-hidden rounded-2xl border border-border/70 bg-background/70 shadow-xl shadow-primary/10 transition hover:scale-[1.01]"
        >
          <img src={src} alt={alt} className="w-full object-cover" />
        </button>
        {caption ? (
          <figcaption className="text-sm text-muted-foreground">
            {caption}
          </figcaption>
        ) : null}
      </figure>

      <Dialog open={open} onOpenChange={setOpen}>
        <DialogContent className="max-w-5xl border-none bg-transparent p-0 shadow-none">
          <DialogTitle className="sr-only">{alt}</DialogTitle>
          <DialogDescription className="sr-only">
            {caption ?? alt}
          </DialogDescription>
          <img
            src={src}
            alt={alt}
            className="max-h-[85vh] w-full rounded-2xl object-contain"
          />
        </DialogContent>
      </Dialog>
    </>
  );
}

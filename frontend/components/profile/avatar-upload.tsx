"use client";

import { useEffect, useState } from "react";
import { Upload, X } from "lucide-react";
import { Button } from "@/components/ui/button";

interface AvatarUploadProps {
  label: string;
  value: File | null;
  initialPreviewUrl?: string | null;
  onChange: (file: File | null) => void;
}

export function AvatarUpload({
  label,
  value,
  initialPreviewUrl = null,
  onChange,
}: AvatarUploadProps) {
  const [previewUrl, setPreviewUrl] = useState<string | null>(
    initialPreviewUrl
  );

  useEffect(() => {
    if (!value) {
      setPreviewUrl(initialPreviewUrl);
      return;
    }

    const nextPreviewUrl = URL.createObjectURL(value);
    setPreviewUrl(nextPreviewUrl);

    return () => {
      URL.revokeObjectURL(nextPreviewUrl);
    };
  }, [initialPreviewUrl, value]);

  return (
    <div className="space-y-3">
      <label
        className="block text-sm font-medium text-foreground"
        htmlFor="profile-avatar"
      >
        {label}
      </label>
      <div className="flex items-center gap-4 rounded-xl border border-dashed border-border/70 bg-muted/30 p-4">
        <div className="flex h-20 w-20 items-center justify-center overflow-hidden rounded-full bg-background text-sm font-semibold text-muted-foreground">
          {previewUrl ? (
            <img
              src={previewUrl}
              alt="Avatar preview"
              className="h-full w-full object-cover"
            />
          ) : (
            <span>No avatar selected</span>
          )}
        </div>
        <div className="flex flex-wrap items-center gap-3">
          <label
            htmlFor="profile-avatar"
            className="inline-flex cursor-pointer items-center gap-2 rounded-md bg-primary px-3 py-2 text-sm font-medium text-primary-foreground"
          >
            <Upload className="h-4 w-4" />
            <span>Choose avatar</span>
          </label>
          <input
            id="profile-avatar"
            aria-label={label}
            type="file"
            accept="image/*"
            className="sr-only"
            onChange={(event) => {
              const file = event.target.files?.[0] ?? null;
              if (file) {
                setPreviewUrl(URL.createObjectURL(file));
              } else {
                setPreviewUrl(initialPreviewUrl);
              }
              onChange(file);
            }}
          />
          <Button
            type="button"
            variant="ghost"
            onClick={() => {
              setPreviewUrl(null);
              onChange(null);
            }}
            disabled={!previewUrl && !value}
            aria-label="Remove avatar"
          >
            <X className="h-4 w-4" />
            Remove avatar
          </Button>
        </div>
      </div>
    </div>
  );
}

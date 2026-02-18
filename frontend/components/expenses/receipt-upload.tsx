"use client";

import { DragEvent, useMemo, useRef, useState } from "react";
import { Camera, File, Upload, X } from "lucide-react";
import { Button } from "@/components/ui/button";
import { cn } from "@/lib/utils";

interface ReceiptUploadProps {
  file: File | null;
  onChange: (file: File | null) => void;
  existingUrl?: string | null;
  existingMimeType?: string | null;
}

function objectUrlFor(file: File | null): string | null {
  if (!file) {
    return null;
  }

  return URL.createObjectURL(file);
}

export function ReceiptUpload({
  file,
  onChange,
  existingUrl,
  existingMimeType,
}: ReceiptUploadProps) {
  const inputRef = useRef<HTMLInputElement | null>(null);
  const cameraRef = useRef<HTMLInputElement | null>(null);
  const [isDragging, setDragging] = useState(false);

  const previewUrl = useMemo(() => objectUrlFor(file), [file]);
  const mimeType = file?.type || existingMimeType || "";
  const displayUrl = previewUrl || existingUrl || null;

  const pickFile = () => inputRef.current?.click();
  const pickCamera = () => cameraRef.current?.click();

  const assignFile = (candidate: File | null | undefined) => {
    if (!candidate) {
      return;
    }

    onChange(candidate);
  };

  const onDrop = (event: DragEvent<HTMLDivElement>) => {
    event.preventDefault();
    setDragging(false);
    assignFile(event.dataTransfer.files?.[0]);
  };

  const onDragOver = (event: DragEvent<HTMLDivElement>) => {
    event.preventDefault();
    setDragging(true);
  };

  const onDragLeave = (event: DragEvent<HTMLDivElement>) => {
    event.preventDefault();
    setDragging(false);
  };

  const clear = () => {
    onChange(null);
    if (inputRef.current) {
      inputRef.current.value = "";
    }
    if (cameraRef.current) {
      cameraRef.current.value = "";
    }
  };

  const isPdf = mimeType.includes("pdf");
  const isImage = mimeType.startsWith("image/");

  return (
    <div className="space-y-3">
      <div
        className={cn(
          "rounded-lg border border-dashed p-4 text-sm transition",
          isDragging ? "border-primary bg-primary/5" : "border-border"
        )}
        onDrop={onDrop}
        onDragOver={onDragOver}
        onDragLeave={onDragLeave}
      >
        <div className="flex flex-wrap items-center gap-2">
          <Button type="button" variant="outline" size="sm" onClick={pickFile}>
            <Upload className="mr-2 h-4 w-4" />
            Upload receipt
          </Button>
          <Button
            type="button"
            variant="outline"
            size="sm"
            onClick={pickCamera}
          >
            <Camera className="mr-2 h-4 w-4" />
            Capture photo
          </Button>
          {displayUrl ? (
            <Button type="button" variant="ghost" size="sm" onClick={clear}>
              <X className="mr-2 h-4 w-4" />
              Remove
            </Button>
          ) : null}
        </div>
        <p className="mt-2 text-xs text-muted-foreground">
          Drag and drop an image or PDF (max 10 MB).
        </p>
      </div>

      <input
        ref={inputRef}
        type="file"
        className="hidden"
        accept="image/*,application/pdf"
        onChange={(event) => assignFile(event.target.files?.[0] || null)}
      />
      <input
        ref={cameraRef}
        type="file"
        className="hidden"
        accept="image/*"
        capture="environment"
        onChange={(event) => assignFile(event.target.files?.[0] || null)}
      />

      {displayUrl ? (
        <div className="rounded-lg border p-3">
          {isImage ? (
            // eslint-disable-next-line @next/next/no-img-element
            <img
              src={displayUrl}
              alt="Receipt preview"
              className="max-h-64 w-auto rounded object-contain"
            />
          ) : isPdf ? (
            <iframe
              title="Receipt PDF preview"
              src={displayUrl}
              className="h-72 w-full rounded border"
            />
          ) : (
            <div className="inline-flex items-center gap-2 text-sm text-muted-foreground">
              <File className="h-4 w-4" />
              File attached
            </div>
          )}
        </div>
      ) : null}
    </div>
  );
}

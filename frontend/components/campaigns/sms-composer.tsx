"use client";

import { Button } from "@/components/ui/button";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";

interface SmsComposerProps {
  value: string;
  onChange: (value: string) => void;
}

function hasUnicode(content: string): boolean {
  return /[^\u0000-\u007f]/.test(content);
}

function countSegments(content: string): { perSegment: number; segments: number } {
  const perSegment = hasUnicode(content) ? 70 : 160;
  const length = content.length;

  if (length === 0) {
    return { perSegment, segments: 1 };
  }

  return {
    perSegment,
    segments: Math.ceil(length / perSegment),
  };
}

export function SmsComposer({ value, onChange }: SmsComposerProps) {
  const { perSegment, segments } = countSegments(value);

  const insertVariable = (variable: string) => {
    onChange(`${value}${value ? " " : ""}${variable}`);
  };

  return (
    <div className="space-y-3 rounded-lg border p-4">
      <div className="flex flex-wrap gap-2">
        <Button
          type="button"
          size="sm"
          variant="outline"
          onClick={() => insertVariable("{{first_name}}")}
        >
          Insert {"{{first_name}}"}
        </Button>
        <Button
          type="button"
          size="sm"
          variant="outline"
          onClick={() => insertVariable("{{company}}")}
        >
          Insert {"{{company}}"}
        </Button>
      </div>

      <div className="space-y-2">
        <Label htmlFor="sms-content">SMS content</Label>
        <Textarea
          id="sms-content"
          rows={8}
          value={value}
          onChange={(event) => onChange(event.target.value)}
        />
      </div>

      <div className="flex items-center justify-between text-xs text-muted-foreground">
        <span>
          {value.length} / {perSegment}
        </span>
        <span>
          {segments} segment{segments > 1 ? "s" : ""}
        </span>
      </div>

      {value.length > 320 && (
        <p className="text-xs font-medium text-amber-600">
          This message will be split across multiple SMS segments.
        </p>
      )}
    </div>
  );
}

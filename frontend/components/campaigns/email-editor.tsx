"use client";

import { useMemo, useState } from "react";
import { Button } from "@/components/ui/button";
import { Textarea } from "@/components/ui/textarea";
import { Label } from "@/components/ui/label";

interface EmailEditorProps {
  value: string;
  onChange: (value: string) => void;
}

function countWords(content: string): number {
  const trimmed = content.trim();
  if (!trimmed) {
    return 0;
  }

  return trimmed.split(/\s+/).length;
}

export function EmailEditor({ value, onChange }: EmailEditorProps) {
  const [sourceMode, setSourceMode] = useState(false);

  const stats = useMemo(
    () => ({
      characters: value.length,
      words: countWords(value),
    }),
    [value]
  );

  const wrap = (before: string, after: string = before) => {
    onChange(`${before}${value}${after}`);
  };

  const insertVariable = (variable: string) => {
    onChange(`${value}${value ? " " : ""}${variable}`);
  };

  return (
    <div className="space-y-3 rounded-lg border p-4">
      <div className="flex flex-wrap gap-2">
        <Button
          type="button"
          variant="outline"
          size="sm"
          onClick={() => wrap("<strong>", "</strong>")}
        >
          Bold
        </Button>
        <Button
          type="button"
          variant="outline"
          size="sm"
          onClick={() => wrap("<em>", "</em>")}
        >
          Italic
        </Button>
        <Button
          type="button"
          variant="outline"
          size="sm"
          onClick={() => wrap("<u>", "</u>")}
        >
          Underline
        </Button>
        <Button
          type="button"
          variant="outline"
          size="sm"
          onClick={() => insertVariable("{{first_name}}")}
        >
          Insert {"{{first_name}}"}
        </Button>
        <Button
          type="button"
          variant="secondary"
          size="sm"
          onClick={() => setSourceMode((current) => !current)}
        >
          HTML source
        </Button>
      </div>

      {sourceMode && (
        <p className="text-xs font-medium text-muted-foreground">
          Source mode enabled
        </p>
      )}

      <div className="space-y-2">
        <Label htmlFor="email-content">Email content</Label>
        <Textarea
          id="email-content"
          aria-label="Email content"
          rows={12}
          value={value}
          onChange={(event) => onChange(event.target.value)}
        />
      </div>

      <p className="text-xs text-muted-foreground">
        {stats.characters} characters - {stats.words} words
      </p>
    </div>
  );
}

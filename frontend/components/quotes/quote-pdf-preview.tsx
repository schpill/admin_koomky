"use client";

import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";

interface QuotePdfPreviewProps {
  html?: string;
}

const DEFAULT_PREVIEW = `
  <html>
    <body style="font-family: Arial, sans-serif; padding: 16px;">
      <h3>Quote preview</h3>
      <p>Select a quote to preview a printable version.</p>
    </body>
  </html>
`;

export function QuotePdfPreview({ html }: QuotePdfPreviewProps) {
  return (
    <Card>
      <CardHeader>
        <CardTitle>PDF preview</CardTitle>
      </CardHeader>
      <CardContent>
        <iframe
          title="quote-pdf-preview"
          srcDoc={html || DEFAULT_PREVIEW}
          className="h-[400px] w-full rounded-md border"
        />
      </CardContent>
    </Card>
  );
}

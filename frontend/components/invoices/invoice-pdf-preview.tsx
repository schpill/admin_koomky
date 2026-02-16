"use client";

import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";

interface InvoicePdfPreviewProps {
  html?: string;
}

const DEFAULT_PREVIEW = `
  <html>
    <body style="font-family: Arial, sans-serif; padding: 16px;">
      <h3>Invoice preview</h3>
      <p>Select an invoice to preview a printable version.</p>
    </body>
  </html>
`;

export function InvoicePdfPreview({ html }: InvoicePdfPreviewProps) {
  return (
    <Card>
      <CardHeader>
        <CardTitle>PDF preview</CardTitle>
      </CardHeader>
      <CardContent>
        <iframe
          title="invoice-pdf-preview"
          srcDoc={html || DEFAULT_PREVIEW}
          className="h-[400px] w-full rounded-md border"
        />
      </CardContent>
    </Card>
  );
}

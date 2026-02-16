"use client";

import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";

interface VatRow {
  rate: string;
  taxable_amount: number;
  vat_amount: number;
}

interface VatSummaryTableProps {
  rows: VatRow[];
  totalVat: number;
}

export function VatSummaryTable({ rows, totalVat }: VatSummaryTableProps) {
  return (
    <Card>
      <CardHeader>
        <CardTitle>VAT summary</CardTitle>
      </CardHeader>
      <CardContent className="space-y-4">
        {rows.length === 0 ? (
          <p className="text-sm text-muted-foreground">
            No VAT entries for this period.
          </p>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full text-sm">
              <thead>
                <tr className="border-b text-left">
                  <th className="pb-3 font-medium text-muted-foreground">
                    Rate
                  </th>
                  <th className="pb-3 font-medium text-muted-foreground">
                    Taxable amount
                  </th>
                  <th className="pb-3 font-medium text-muted-foreground">
                    VAT amount
                  </th>
                </tr>
              </thead>
              <tbody>
                {rows.map((row) => (
                  <tr key={row.rate} className="border-b last:border-0">
                    <td className="py-3">{row.rate}%</td>
                    <td className="py-3">
                      {Number(row.taxable_amount).toFixed(2)} EUR
                    </td>
                    <td className="py-3">
                      {Number(row.vat_amount).toFixed(2)} EUR
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}

        <div className="rounded-md border p-3 text-sm font-semibold">
          Total VAT: {Number(totalVat).toFixed(2)} EUR
        </div>
      </CardContent>
    </Card>
  );
}

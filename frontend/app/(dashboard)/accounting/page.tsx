"use client";

import Link from "next/link";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { FileText, Receipt, Download, Calendar } from "lucide-react";

const ACCOUNTING_CARDS = [
  {
    title: "FEC Export",
    description:
      "Generate FEC-compliant accounting export file for French tax authorities",
    href: "/accounting/fec",
    icon: FileText,
  },
  {
    title: "VAT Declaration",
    description: "View and export VAT declaration report (CA3-style)",
    href: "/accounting/vat",
    icon: Receipt,
  },
  {
    title: "Accounting Export",
    description: "Export to Pennylane, Sage, or generic CSV format",
    href: "/accounting/export",
    icon: Download,
  },
  {
    title: "Fiscal Year Summary",
    description: "View closing summary for the fiscal year",
    href: "/accounting/fiscal-year",
    icon: Calendar,
  },
];

export default function AccountingPage() {
  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-3xl font-bold">Accounting</h1>
        <p className="text-sm text-muted-foreground">
          Manage accounting exports and tax compliance reports
        </p>
      </div>

      <div className="grid gap-4 md:grid-cols-2">
        {ACCOUNTING_CARDS.map((card) => (
          <Link key={card.href} href={card.href}>
            <Card className="h-full transition-shadow hover:shadow-md">
              <CardHeader>
                <div className="flex items-center gap-3">
                  <card.icon className="h-8 w-8 text-primary" />
                  <CardTitle>{card.title}</CardTitle>
                </div>
              </CardHeader>
              <CardContent>
                <p className="text-sm text-muted-foreground">
                  {card.description}
                </p>
              </CardContent>
            </Card>
          </Link>
        ))}
      </div>
    </div>
  );
}

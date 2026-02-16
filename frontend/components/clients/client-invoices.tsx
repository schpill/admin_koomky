"use client";

import Link from "next/link";
import { useEffect } from "react";
import { EmptyState } from "@/components/ui/empty-state";
import { FileText } from "lucide-react";
import { useInvoiceStore } from "@/lib/stores/invoices";
import { InvoiceStatusBadge } from "@/components/invoices/invoice-status-badge";

interface ClientInvoicesProps {
  clientId: string;
}

export function ClientInvoices({ clientId }: ClientInvoicesProps) {
  const { invoices, isLoading, fetchInvoices } = useInvoiceStore();

  useEffect(() => {
    fetchInvoices({ client_id: clientId, per_page: 50 });
  }, [clientId, fetchInvoices]);

  if (isLoading && invoices.length === 0) {
    return <p className="text-sm text-muted-foreground">Loading invoices...</p>;
  }

  if (invoices.length === 0) {
    return (
      <EmptyState
        icon={<FileText className="h-12 w-12" />}
        title="No invoices for this client"
        description="Invoices linked to this client will appear here."
      />
    );
  }

  return (
    <div className="overflow-x-auto">
      <table className="w-full text-sm">
        <thead>
          <tr className="border-b text-left">
            <th className="pb-3 font-medium text-muted-foreground">Number</th>
            <th className="pb-3 font-medium text-muted-foreground">
              Issue date
            </th>
            <th className="pb-3 font-medium text-muted-foreground">Due date</th>
            <th className="pb-3 font-medium text-muted-foreground">Total</th>
            <th className="pb-3 font-medium text-muted-foreground">Status</th>
          </tr>
        </thead>
        <tbody>
          {invoices.map((invoice) => (
            <tr key={invoice.id} className="border-b last:border-0">
              <td className="py-3">
                <Link
                  href={`/invoices/${invoice.id}`}
                  className="font-medium text-primary hover:underline"
                >
                  {invoice.number}
                </Link>
              </td>
              <td className="py-3 text-muted-foreground">
                {invoice.issue_date}
              </td>
              <td className="py-3 text-muted-foreground">{invoice.due_date}</td>
              <td className="py-3">
                {Number(invoice.total || 0).toFixed(2)} EUR
              </td>
              <td className="py-3">
                <InvoiceStatusBadge status={invoice.status} />
              </td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}

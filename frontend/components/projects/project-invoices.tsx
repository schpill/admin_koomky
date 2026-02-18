"use client";

import Link from "next/link";
import { useEffect, useState } from "react";
import { toast } from "sonner";
import { Button } from "@/components/ui/button";
import { EmptyState } from "@/components/ui/empty-state";
import { FilePlus2, FileText } from "lucide-react";
import { useInvoiceStore } from "@/lib/stores/invoices";
import { InvoiceStatusBadge } from "@/components/invoices/invoice-status-badge";
import { apiClient } from "@/lib/api";

interface ProjectInvoicesProps {
  projectId: string;
}

export function ProjectInvoices({ projectId }: ProjectInvoicesProps) {
  const { invoices, isLoading, fetchInvoices } = useInvoiceStore();
  const [isGenerating, setGenerating] = useState(false);
  const [isGeneratingBillable, setGeneratingBillable] = useState(false);

  useEffect(() => {
    fetchInvoices({ project_id: projectId, per_page: 50 });
  }, [fetchInvoices, projectId]);

  const generateInvoice = async () => {
    setGenerating(true);
    try {
      await apiClient.post(`/projects/${projectId}/generate-invoice`);
      await fetchInvoices({ project_id: projectId, per_page: 50 });
      toast.success("Invoice generated from project time entries");
    } catch (error) {
      toast.error((error as Error).message || "Unable to generate invoice");
    } finally {
      setGenerating(false);
    }
  };

  const generateInvoiceWithBillableExpenses = async () => {
    setGeneratingBillable(true);
    try {
      await apiClient.post(`/projects/${projectId}/generate-invoice`, {
        include_billable_expenses: true,
      });
      await fetchInvoices({ project_id: projectId, per_page: 50 });
      toast.success("Invoice generated with billable expenses");
    } catch (error) {
      toast.error((error as Error).message || "Unable to generate invoice");
    } finally {
      setGeneratingBillable(false);
    }
  };

  return (
    <div className="space-y-3">
      <div className="flex flex-wrap justify-end gap-2">
        <Button
          type="button"
          variant="outline"
          onClick={generateInvoice}
          disabled={isGenerating}
        >
          <FilePlus2 className="mr-2 h-4 w-4" />
          {isGenerating
            ? "Generating..."
            : "Generate invoice from unbilled time"}
        </Button>
        <Button
          type="button"
          variant="outline"
          onClick={generateInvoiceWithBillableExpenses}
          disabled={isGeneratingBillable}
        >
          <FilePlus2 className="mr-2 h-4 w-4" />
          {isGeneratingBillable ? "Generating..." : "Invoice billable expenses"}
        </Button>
      </div>

      {isLoading && invoices.length === 0 ? (
        <p className="text-sm text-muted-foreground">Loading invoices...</p>
      ) : invoices.length === 0 ? (
        <EmptyState
          icon={<FileText className="h-12 w-12" />}
          title="No invoices"
          description="Generate the first invoice from project time entries."
        />
      ) : (
        <div className="overflow-x-auto">
          <table className="w-full text-sm">
            <thead>
              <tr className="border-b text-left">
                <th className="pb-3 font-medium text-muted-foreground">
                  Number
                </th>
                <th className="pb-3 font-medium text-muted-foreground">
                  Issue date
                </th>
                <th className="pb-3 font-medium text-muted-foreground">
                  Due date
                </th>
                <th className="pb-3 font-medium text-muted-foreground">
                  Total
                </th>
                <th className="pb-3 font-medium text-muted-foreground">
                  Status
                </th>
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
                  <td className="py-3 text-muted-foreground">
                    {invoice.due_date}
                  </td>
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
      )}
    </div>
  );
}

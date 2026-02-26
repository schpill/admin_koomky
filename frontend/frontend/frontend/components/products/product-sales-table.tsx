"use client";

import { useMemo } from "react";
import {
  useReactTable,
  getCoreRowModel,
  getFilteredRowModel,
  getPaginationRowModel,
  getSortedRowModel,
  ColumnDef,
  flexRender,
} from "@tanstack/react-table";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table";
import { ProductSale } from "@/stores/products";
import { ArrowUpDown, ChevronLeft, ChevronRight } from "lucide-react";

interface ProductSalesTableProps {
  sales: ProductSale[];
  isLoading?: boolean;
}

const statusConfig = {
  pending: { label: "En attente", className: "bg-yellow-100 text-yellow-800" },
  confirmed: { label: "Confirmée", className: "bg-green-100 text-green-800" },
  delivered: { label: "Livrée", className: "bg-blue-100 text-blue-800" },
  cancelled: { label: "Annulée", className: "bg-red-100 text-red-800" },
  refunded: { label: "Remboursée", className: "bg-gray-100 text-gray-800" },
};

export function ProductSalesTable({
  sales,
  isLoading,
}: ProductSalesTableProps) {
  const columns = useMemo<ColumnDef<ProductSale>[]>(
    () => [
      {
        accessorKey: "client.name",
        header: ({ column }) => (
          <Button
            variant="ghost"
            onClick={() => column.toggleSorting(column.getIsSorted() === "asc")}
          >
            Client
            <ArrowUpDown className="ml-2 h-4 w-4" />
          </Button>
        ),
        cell: ({ row }) => (
          <div className="font-medium">
            {row.original.client?.name || "Client inconnu"}
          </div>
        ),
      },
      {
        accessorKey: "total_price",
        header: ({ column }) => (
          <Button
            variant="ghost"
            onClick={() => column.toggleSorting(column.getIsSorted() === "asc")}
          >
            Montant
            <ArrowUpDown className="ml-2 h-4 w-4" />
          </Button>
        ),
        cell: ({ row }) => (
          <div className="font-medium">
            {new Intl.NumberFormat("fr-FR", {
              style: "currency",
              currency: row.original.currency_code,
            }).format(row.original.total_price)}
          </div>
        ),
      },
      {
        accessorKey: "status",
        header: "Statut",
        cell: ({ row }) => {
          const config = statusConfig[row.original.status];
          return <Badge className={config.className}>{config.label}</Badge>;
        },
        filterFn: (row, id, value) => {
          return value.length === 0 || value.includes(row.original.status);
        },
      },
      {
        accessorKey: "sold_at",
        header: ({ column }) => (
          <Button
            variant="ghost"
            onClick={() => column.toggleSorting(column.getIsSorted() === "asc")}
          >
            Date
            <ArrowUpDown className="ml-2 h-4 w-4" />
          </Button>
        ),
        cell: ({ row }) => (
          <div>
            {row.original.sold_at
              ? new Date(row.original.sold_at).toLocaleDateString("fr-FR")
              : "N/A"}
          </div>
        ),
      },
    ],
    []
  );

  const table = useReactTable({
    data: sales,
    columns,
    getCoreRowModel: getCoreRowModel(),
    getFilteredRowModel: getFilteredRowModel(),
    getPaginationRowModel: getPaginationRowModel(),
    getSortedRowModel: getSortedRowModel(),
    initialState: {
      sorting: [{ id: "sold_at", desc: true }],
    },
  });

  const handleStatusFilter = (status: string) => {
    const column = table.getColumn("status");
    if (!column) return;

    const currentFilter = (column.getFilterValue() as string[]) || [];

    if (currentFilter.includes(status)) {
      column.setFilterValue(currentFilter.filter((s) => s !== status));
    } else {
      column.setFilterValue([...currentFilter, status]);
    }
  };

  if (isLoading) {
    return (
      <div className="space-y-4">
        <div className="h-10 bg-muted rounded animate-pulse" />
        <div className="space-y-2">
          {Array.from({ length: 5 }).map((_, i) => (
            <div key={i} className="h-16 bg-muted rounded animate-pulse" />
          ))}
        </div>
      </div>
    );
  }

  return (
    <div className="space-y-4">
      <div className="flex items-center gap-4">
        <div className="flex items-center gap-2">
          <span className="text-sm text-muted-foreground">
            Filtrer par statut:
          </span>
          {Object.entries(statusConfig).map(([status, config]) => (
            <Button
              key={status}
              variant="outline"
              size="sm"
              onClick={() => handleStatusFilter(status)}
              className={
                (
                  (table.getColumn("status")?.getFilterValue() as string[]) ||
                  []
                ).includes(status)
                  ? "bg-muted"
                  : ""
              }
            >
              {config.label}
            </Button>
          ))}
        </div>

        <Button
          variant="outline"
          size="sm"
          onClick={() => table.getColumn("status")?.setFilterValue([])}
        >
          Tout afficher
        </Button>
      </div>

      <div className="rounded-md border">
        <Table>
          <TableHeader>
            {table.getHeaderGroups().map((headerGroup) => (
              <TableRow key={headerGroup.id}>
                {headerGroup.headers.map((header) => (
                  <TableHead key={header.id}>
                    {header.isPlaceholder
                      ? null
                      : flexRender(
                          header.column.columnDef.header,
                          header.getContext()
                        )}
                  </TableHead>
                ))}
              </TableRow>
            ))}
          </TableHeader>
          <TableBody>
            {table.getRowModel().rows?.length ? (
              table.getRowModel().rows.map((row) => (
                <TableRow key={row.id}>
                  {row.getVisibleCells().map((cell) => (
                    <TableCell key={cell.id}>
                      {flexRender(
                        cell.column.columnDef.cell,
                        cell.getContext()
                      )}
                    </TableCell>
                  ))}
                </TableRow>
              ))
            ) : (
              <TableRow>
                <TableCell
                  colSpan={columns.length}
                  className="h-24 text-center"
                >
                  Aucune vente trouvée.
                </TableCell>
              </TableRow>
            )}
          </TableBody>
        </Table>
      </div>

      <div className="flex items-center justify-end space-x-2 py-4">
        <div className="flex-1 text-sm text-muted-foreground">
          {table.getFilteredSelectedRowModel().rows.length} sur{" "}
          {table.getFilteredRowModel().rows.length} ventes sélectionnées.
        </div>
        <div className="space-x-2">
          <Button
            variant="outline"
            size="sm"
            onClick={() => table.previousPage()}
            disabled={!table.getCanPreviousPage()}
          >
            <ChevronLeft className="h-4 w-4" />
            Précédent
          </Button>
          <Button
            variant="outline"
            size="sm"
            onClick={() => table.nextPage()}
            disabled={!table.getCanNextPage()}
          >
            Suivant
            <ChevronRight className="h-4 w-4" />
          </Button>
        </div>
      </div>
    </div>
  );
}

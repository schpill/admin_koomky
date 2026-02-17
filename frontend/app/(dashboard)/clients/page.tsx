"use client";

import { useEffect, useState } from "react";
import {
  Plus,
  Search,
  MoreHorizontal,
  UserPlus,
  Filter,
  ArrowUpDown,
  Eye,
  Edit,
  Trash2,
} from "lucide-react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Card, CardContent, CardHeader } from "@/components/ui/card";
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { Badge } from "@/components/ui/badge";
import { useClientStore } from "@/lib/stores/clients";
import { EmptyState } from "@/components/ui/empty-state";
import { Skeleton } from "@/components/ui/skeleton";
import { CreateClientDialog } from "@/components/clients/create-client-dialog";
import { CsvActions } from "@/components/clients/csv-actions";
import Link from "next/link";
import { useI18n } from "@/components/providers/i18n-provider";
import { ConfirmationDialog } from "@/components/common/confirmation-dialog";

export default function ClientsPage() {
  const { clients, isLoading, fetchClients, deleteClient } = useClientStore();
  const { t } = useI18n();
  const [search, setSearch] = useState("");
  const [status, setStatus] = useState<string>("all");
  const [sortBy, setBy] = useState<string>("created_at");
  const [sortOrder, setOrder] = useState<string>("desc");
  const [archiveTarget, setArchiveTarget] = useState<{
    id: string;
    name: string;
  } | null>(null);

  const getStatusLabel = (value: string) => {
    const translationKey = `clients.status.${value}`;
    const translated = t(translationKey);
    return translated === translationKey ? value : translated;
  };

  useEffect(() => {
    fetchClients({
      search,
      status: status === "all" ? undefined : status,
      sort_by: sortBy,
      sort_order: sortOrder,
    });
  }, [fetchClients, search, status, sortBy, sortOrder]);

  const handleSearch = (e: React.FormEvent) => {
    e.preventDefault();
    fetchClients({
      search,
      status: status === "all" ? undefined : status,
      sort_by: sortBy,
      sort_order: sortOrder,
    });
  };

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <h1 className="text-3xl font-bold">{t("clients.list.title")}</h1>
        <div className="flex gap-2">
          <CsvActions />
          <CreateClientDialog />
        </div>
      </div>

      <Card>
        <CardHeader className="pb-3 border-b">
          <div className="flex flex-col md:flex-row gap-4 items-center justify-between">
            <form onSubmit={handleSearch} className="relative w-full max-w-sm">
              <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
              <Input
                placeholder={t("clients.list.searchPlaceholder")}
                className="pl-9"
                value={search}
                onChange={(e) => setSearch(e.target.value)}
              />
            </form>
            <div className="flex flex-wrap items-center gap-2 w-full md:w-auto">
              <div className="flex items-center gap-2">
                <Filter className="h-4 w-4 text-muted-foreground" />
                <Select value={status} onValueChange={setStatus}>
                  <SelectTrigger className="w-[130px] h-9">
                    <SelectValue
                      placeholder={t("clients.list.statusPlaceholder")}
                    />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="all">
                      {t("clients.status.all")}
                    </SelectItem>
                    <SelectItem value="active">
                      {t("clients.status.active")}
                    </SelectItem>
                    <SelectItem value="inactive">
                      {t("clients.status.inactive")}
                    </SelectItem>
                    <SelectItem value="lead">
                      {t("clients.status.lead")}
                    </SelectItem>
                  </SelectContent>
                </Select>
              </div>
              <div className="flex items-center gap-2">
                <ArrowUpDown className="h-4 w-4 text-muted-foreground" />
                <Select
                  value={`${sortBy}-${sortOrder}`}
                  onValueChange={(val) => {
                    const [b, o] = val.split("-");
                    setBy(b);
                    setOrder(o);
                  }}
                >
                  <SelectTrigger className="w-[180px] h-9">
                    <SelectValue
                      placeholder={t("clients.list.sortPlaceholder")}
                    />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="created_at-desc">
                      {t("clients.list.sortNewest")}
                    </SelectItem>
                    <SelectItem value="created_at-asc">
                      {t("clients.list.sortOldest")}
                    </SelectItem>
                    <SelectItem value="name-asc">
                      {t("clients.list.sortNameAsc")}
                    </SelectItem>
                    <SelectItem value="name-desc">
                      {t("clients.list.sortNameDesc")}
                    </SelectItem>
                  </SelectContent>
                </Select>
              </div>
            </div>
          </div>
        </CardHeader>
        <CardContent className="pt-6">
          {isLoading && clients.length === 0 ? (
            <div className="space-y-2">
              <Skeleton className="h-12 w-full" />
              <Skeleton className="h-12 w-full" />
              <Skeleton className="h-12 w-full" />
            </div>
          ) : clients.length === 0 ? (
            <EmptyState
              icon={<UserPlus className="h-12 w-12" />}
              title={t("clients.list.emptyTitle")}
              description={t("clients.list.emptyDescription")}
              action={<CreateClientDialog />}
            />
          ) : (
            <div className="relative overflow-x-auto">
              <table className="min-w-[680px] w-full text-left text-sm">
                <thead>
                  <tr className="border-b">
                    <th className="pb-3 font-medium text-muted-foreground">
                      {t("clients.table.reference")}
                    </th>
                    <th className="pb-3 font-medium text-muted-foreground">
                      {t("clients.table.name")}
                    </th>
                    <th className="hidden pb-3 font-medium text-muted-foreground md:table-cell">
                      {t("clients.table.email")}
                    </th>
                    <th className="hidden pb-3 font-medium text-muted-foreground sm:table-cell">
                      {t("clients.table.status")}
                    </th>
                    <th className="pb-3 font-medium text-muted-foreground text-right">
                      {t("clients.table.actions")}
                    </th>
                  </tr>
                </thead>
                <tbody>
                  {clients.map((client) => (
                    <tr
                      key={client.id}
                      className="border-b last:border-0 hover:bg-muted/50 transition-colors"
                    >
                      <td className="py-4 font-mono text-xs">
                        {client.reference}
                      </td>
                      <td className="py-4">
                        <Link
                          href={`/clients/${client.id}`}
                          className="font-medium hover:underline text-primary"
                        >
                          {client.name}
                        </Link>
                      </td>
                      <td className="hidden py-4 text-muted-foreground md:table-cell">
                        {client.email || "-"}
                      </td>
                      <td className="hidden py-4 sm:table-cell">
                        <Badge
                          variant={
                            client.status === "active" ? "default" : "secondary"
                          }
                        >
                          {getStatusLabel(client.status)}
                        </Badge>
                      </td>
                      <td className="py-4 text-right">
                        <DropdownMenu>
                          <DropdownMenuTrigger asChild>
                            <Button
                              variant="ghost"
                              size="icon"
                              aria-label={t("clients.list.openActionsMenu")}
                            >
                              <MoreHorizontal className="h-4 w-4" />
                            </Button>
                          </DropdownMenuTrigger>
                          <DropdownMenuContent align="end">
                            <DropdownMenuLabel>
                              {t("clients.table.actions")}
                            </DropdownMenuLabel>
                            <DropdownMenuItem asChild>
                              <Link
                                href={`/clients/${client.id}`}
                                className="flex items-center"
                              >
                                <Eye className="mr-2 h-4 w-4" />{" "}
                                {t("clients.list.viewDetails")}
                              </Link>
                            </DropdownMenuItem>
                            <DropdownMenuItem asChild>
                              <Link
                                href={`/clients/${client.id}/edit`}
                                className="flex items-center"
                              >
                                <Edit className="mr-2 h-4 w-4" />{" "}
                                {t("clients.list.editClient")}
                              </Link>
                            </DropdownMenuItem>
                            <DropdownMenuSeparator />
                            <DropdownMenuItem
                              className="text-destructive"
                              onClick={() =>
                                setArchiveTarget({
                                  id: client.id,
                                  name: client.name,
                                })
                              }
                            >
                              <Trash2 className="mr-2 h-4 w-4" />{" "}
                              {t("clients.list.archiveClient")}
                            </DropdownMenuItem>
                          </DropdownMenuContent>
                        </DropdownMenu>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          )}
        </CardContent>
      </Card>

      <ConfirmationDialog
        open={archiveTarget !== null}
        onOpenChange={(open) => {
          if (!open) {
            setArchiveTarget(null);
          }
        }}
        onConfirm={() => {
          if (!archiveTarget) {
            return;
          }

          deleteClient(archiveTarget.id);
          setArchiveTarget(null);
        }}
        title={t("clients.list.archiveClient")}
        description={t("clients.list.archiveConfirmation")}
        confirmText={t("clients.list.archiveClient")}
        variant="destructive"
      />
    </div>
  );
}

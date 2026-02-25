"use client";

import { useEffect, useState } from "react";
import { useRouter } from "next/navigation";
import { useTicketStore } from "@/lib/stores/tickets";
import type {
  Ticket,
  TicketStatus,
  TicketPriority,
} from "@/lib/stores/tickets";
import { TicketStatusBadge } from "@/components/tickets/ticket-status-badge";
import { TicketPriorityBadge } from "@/components/tickets/ticket-priority-badge";
import { TicketStatsCard } from "@/components/tickets/ticket-stats-card";
import { TicketFormDialog } from "@/components/tickets/ticket-form-dialog";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Checkbox } from "@/components/ui/checkbox";
import { Label } from "@/components/ui/label";
import { useI18n } from "@/components/providers/i18n-provider";
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table";
import { Plus, Search, AlertTriangle } from "lucide-react";
import { cn } from "@/lib/utils";

export default function TicketsPage() {
  const router = useRouter();
  const { t } = useI18n();
  const {
    tickets,
    stats,
    pagination,
    isLoading,
    fetchTickets,
    fetchStats,
    createTicket,
    searchQuery,
    filters,
    setSearchQuery,
    setFilters,
  } = useTicketStore();

  const [showCreateDialog, setShowCreateDialog] = useState(false);
  const [createLoading, setCreateLoading] = useState(false);
  const [searchInput, setSearchInput] = useState("");

  useEffect(() => {
    fetchTickets();
    fetchStats();
  }, []);

  // Debounced search
  useEffect(() => {
    const timer = setTimeout(() => {
      setSearchQuery(searchInput);
      fetchTickets({ q: searchInput || undefined, ...filters });
    }, 300);
    return () => clearTimeout(timer);
  }, [searchInput]);

  const handleStatusFilter = (status: TicketStatus, checked: boolean) => {
    const newFilters = {
      ...filters,
      status: checked ? status : ("" as TicketStatus | ""),
    };
    setFilters(newFilters);
    fetchTickets({ ...newFilters, q: searchQuery || undefined });
  };

  const handlePriorityFilter = (priority: TicketPriority, checked: boolean) => {
    const newFilters = {
      ...filters,
      priority: checked ? priority : ("" as TicketPriority | ""),
    };
    setFilters(newFilters);
    fetchTickets({ ...newFilters, q: searchQuery || undefined });
  };

  const handleOverdueFilter = (checked: boolean) => {
    const newFilters = { ...filters, overdue: checked || undefined };
    setFilters(newFilters);
    fetchTickets({ ...newFilters, q: searchQuery || undefined });
  };

  const handleCreate = async (data: any) => {
    setCreateLoading(true);
    try {
      const ticket = await createTicket(data);
      setShowCreateDialog(false);
      router.push(`/tickets/${ticket.id}`);
    } catch {
      // error handled in store
    } finally {
      setCreateLoading(false);
    }
  };

  const isOverdue = (ticket: Ticket): boolean => {
    if (!ticket.deadline) return false;
    if (ticket.status === "resolved" || ticket.status === "closed")
      return false;
    return new Date(ticket.deadline) < new Date();
  };

  const formatDate = (dateStr: string | null): string => {
    if (!dateStr) return "\u2014";
    return new Date(dateStr).toLocaleDateString();
  };

  return (
    <div className="flex h-full flex-col space-y-6 p-8">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold tracking-tight">
            {t("tickets.title")}
          </h1>
          <p className="text-muted-foreground">{t("tickets.subtitle")}</p>
        </div>
        <Button onClick={() => setShowCreateDialog(true)}>
          <Plus className="mr-2 h-4 w-4" />
          {t("tickets.newTicket")}
        </Button>
      </div>

      {/* Stats */}
      <TicketStatsCard stats={stats} isLoading={isLoading} />

      <div className="flex gap-6">
        {/* Filters sidebar */}
        <aside className="w-56 shrink-0 space-y-6">
          {/* Search */}
          <div className="relative">
            <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
            <Input
              placeholder={t("tickets.searchPlaceholder")}
              value={searchInput}
              onChange={(e) => setSearchInput(e.target.value)}
              className="pl-9"
            />
          </div>

          {/* Status filter */}
          <div className="space-y-2">
            <p className="text-sm font-semibold">
              {t("tickets.filters.status")}
            </p>
            {(
              [
                "open",
                "in_progress",
                "pending",
                "resolved",
                "closed",
              ] as TicketStatus[]
            ).map((s) => (
              <div key={s} className="flex items-center gap-2">
                <Checkbox
                  id={`status-${s}`}
                  checked={filters.status === s}
                  onCheckedChange={(v) => handleStatusFilter(s, !!v)}
                />
                <Label htmlFor={`status-${s}`} className="text-sm capitalize">
                  {t(`tickets.status.${s}`)}
                </Label>
              </div>
            ))}
          </div>

          {/* Priority filter */}
          <div className="space-y-2">
            <p className="text-sm font-semibold">
              {t("tickets.filters.priority")}
            </p>
            {(["low", "normal", "high", "urgent"] as TicketPriority[]).map(
              (p) => (
                <div key={p} className="flex items-center gap-2">
                  <Checkbox
                    id={`priority-${p}`}
                    checked={filters.priority === p}
                    onCheckedChange={(v) => handlePriorityFilter(p, !!v)}
                  />
                  <Label
                    htmlFor={`priority-${p}`}
                    className="text-sm capitalize"
                  >
                    {t(`tickets.priority.${p}`)}
                  </Label>
                </div>
              )
            )}
          </div>

          {/* Overdue toggle */}
          <div className="flex items-center gap-2">
            <Checkbox
              id="overdue"
              checked={!!filters.overdue}
              onCheckedChange={(v) => handleOverdueFilter(!!v)}
            />
            <Label htmlFor="overdue" className="text-sm">
              {t("tickets.overdueOnly")}
            </Label>
          </div>
        </aside>

        {/* Main table */}
        <div className="flex-1 overflow-auto">
          {isLoading ? (
            <div className="space-y-2">
              {Array.from({ length: 5 }).map((_, i) => (
                <div
                  key={i}
                  className="h-12 animate-pulse rounded bg-gray-100"
                />
              ))}
            </div>
          ) : tickets.length === 0 ? (
            <div className="flex flex-col items-center justify-center py-16 text-center">
              <p className="text-muted-foreground">{t("tickets.noTickets")}</p>
              <Button
                variant="outline"
                className="mt-4"
                onClick={() => setShowCreateDialog(true)}
              >
                {t("tickets.createFirst")}
              </Button>
            </div>
          ) : (
            <>
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>{t("tickets.table.title")}</TableHead>
                    <TableHead>{t("tickets.table.status")}</TableHead>
                    <TableHead>{t("tickets.table.priority")}</TableHead>
                    <TableHead>{t("tickets.table.client")}</TableHead>
                    <TableHead>{t("tickets.table.assignee")}</TableHead>
                    <TableHead>{t("tickets.table.deadline")}</TableHead>
                    <TableHead>{t("tickets.table.created")}</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {tickets.map((ticket) => (
                    <TableRow
                      key={ticket.id}
                      className="cursor-pointer hover:bg-muted/50"
                      onClick={() => router.push(`/tickets/${ticket.id}`)}
                    >
                      <TableCell className="font-medium">
                        {ticket.title}
                      </TableCell>
                      <TableCell>
                        <TicketStatusBadge status={ticket.status} />
                      </TableCell>
                      <TableCell>
                        <TicketPriorityBadge priority={ticket.priority} />
                      </TableCell>
                      <TableCell className="text-muted-foreground">
                        {ticket.client?.name ?? "Divers"}
                      </TableCell>
                      <TableCell className="text-muted-foreground">
                        {ticket.assignee?.name ?? "\u2014"}
                      </TableCell>
                      <TableCell>
                        {ticket.deadline ? (
                          <span
                            className={cn(
                              "text-sm",
                              isOverdue(ticket)
                                ? "font-semibold text-red-600"
                                : "text-muted-foreground"
                            )}
                          >
                            {isOverdue(ticket) && (
                              <AlertTriangle className="mr-1 inline h-3 w-3" />
                            )}
                            {formatDate(ticket.deadline)}
                          </span>
                        ) : (
                          <span className="text-muted-foreground">
                            {"\u2014"}
                          </span>
                        )}
                      </TableCell>
                      <TableCell className="text-muted-foreground">
                        {formatDate(ticket.created_at)}
                      </TableCell>
                    </TableRow>
                  ))}
                </TableBody>
              </Table>

              {/* Pagination */}
              {pagination && pagination.last_page > 1 && (
                <div className="mt-4 flex items-center justify-between text-sm text-muted-foreground">
                  <span>
                    {t("tickets.showingOf", {
                      count: String(tickets.length),
                      total: String(pagination.total),
                    })}
                  </span>
                  <div className="flex gap-2">
                    {Array.from(
                      { length: pagination.last_page },
                      (_, i) => i + 1
                    ).map((page) => (
                      <Button
                        key={page}
                        variant={
                          page === pagination.current_page
                            ? "default"
                            : "outline"
                        }
                        size="sm"
                        onClick={() =>
                          fetchTickets({
                            page,
                            ...filters,
                            q: searchQuery || undefined,
                          })
                        }
                      >
                        {page}
                      </Button>
                    ))}
                  </div>
                </div>
              )}
            </>
          )}
        </div>
      </div>

      {/* Create dialog */}
      <TicketFormDialog
        open={showCreateDialog}
        onOpenChange={setShowCreateDialog}
        onSubmit={handleCreate}
        isLoading={createLoading}
      />
    </div>
  );
}

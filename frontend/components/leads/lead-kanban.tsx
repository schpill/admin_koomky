"use client";

import { useCallback, useEffect, useState } from "react";
import Link from "next/link";
import { GripVertical, Users, DollarSign, TrendingUp } from "lucide-react";
import { Card, CardContent } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { CurrencyAmount } from "@/components/shared/currency-amount";
import { useLeadStore, Lead } from "@/lib/stores/leads";
import { cn } from "@/lib/utils";
import { toast } from "sonner";

const COLUMN_ORDER: Lead["status"][] = [
  "new",
  "contacted",
  "qualified",
  "proposal_sent",
  "negotiating",
];

const TERMINAL_COLUMNS: Lead["status"][] = ["won", "lost"];

const STATUS_CONFIG: Record<
  string,
  { label: string; color: string; bgColor: string }
> = {
  new: { label: "New", color: "text-blue-700", bgColor: "bg-blue-50" },
  contacted: {
    label: "Contacted",
    color: "text-yellow-700",
    bgColor: "bg-yellow-50",
  },
  qualified: {
    label: "Qualified",
    color: "text-purple-700",
    bgColor: "bg-purple-50",
  },
  proposal_sent: {
    label: "Proposal Sent",
    color: "text-orange-700",
    bgColor: "bg-orange-50",
  },
  negotiating: {
    label: "Negotiating",
    color: "text-pink-700",
    bgColor: "bg-pink-50",
  },
  won: { label: "Won", color: "text-green-700", bgColor: "bg-green-50" },
  lost: { label: "Lost", color: "text-red-700", bgColor: "bg-red-50" },
};

interface LeadKanbanProps {
  onLeadClick?: (lead: Lead) => void;
  showTerminalColumns?: boolean;
}

export function LeadKanban({
  onLeadClick,
  showTerminalColumns = false,
}: LeadKanbanProps) {
  const { pipeline, fetchPipeline, updateStatus, isLoading } = useLeadStore();
  const [draggedLead, setDraggedLead] = useState<Lead | null>(null);
  const [dragOverStatus, setDragOverStatus] = useState<string | null>(null);

  useEffect(() => {
    fetchPipeline();
  }, [fetchPipeline]);

  const handleDragStart = useCallback((lead: Lead) => {
    setDraggedLead(lead);
  }, []);

  const handleDragEnd = useCallback(() => {
    setDraggedLead(null);
    setDragOverStatus(null);
  }, []);

  const handleDragOver = useCallback((e: React.DragEvent, status: string) => {
    e.preventDefault();
    setDragOverStatus(status);
  }, []);

  const handleDrop = useCallback(
    async (e: React.DragEvent, newStatus: string) => {
      e.preventDefault();
      setDragOverStatus(null);

      if (!draggedLead || draggedLead.status === newStatus) {
        setDraggedLead(null);
        return;
      }

      try {
        await updateStatus(draggedLead.id, newStatus);
      } catch (error) {
        toast.error(
          error instanceof Error
            ? error.message
            : "Failed to update lead status"
        );
      }

      setDraggedLead(null);
    },
    [draggedLead, updateStatus]
  );

  const columns = showTerminalColumns
    ? [...COLUMN_ORDER, ...TERMINAL_COLUMNS]
    : COLUMN_ORDER;

  if (!pipeline && isLoading) {
    return (
      <div className="flex h-64 items-center justify-center text-muted-foreground">
        Loading pipeline...
      </div>
    );
  }

  if (!pipeline) {
    return (
      <div className="flex h-64 items-center justify-center text-muted-foreground">
        No pipeline data available
      </div>
    );
  }

  return (
    <div className="space-y-4">
      <div className="flex items-center justify-between rounded-lg bg-muted/50 p-4">
        <div className="flex items-center gap-6 text-sm">
          <div className="flex items-center gap-2">
            <Users className="h-4 w-4 text-muted-foreground" />
            <span className="font-medium">
              {Object.values(pipeline.column_stats).reduce(
                (sum, stat) => sum + stat.count,
                0
              )}{" "}
              Leads
            </span>
          </div>
          <div className="flex items-center gap-2">
            <DollarSign className="h-4 w-4 text-muted-foreground" />
            <span className="font-medium">
              <CurrencyAmount
                amount={pipeline.total_pipeline_value}
                currency="EUR"
              />
            </span>
          </div>
        </div>
      </div>

      <div
        className={cn(
          "grid gap-4",
          showTerminalColumns
            ? "grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-7"
            : "grid-cols-2 md:grid-cols-3 lg:grid-cols-5"
        )}
      >
        {columns.map((status) => {
          const config = STATUS_CONFIG[status];
          const leads = pipeline.columns[status] || [];
          const stats = pipeline.column_stats[status];
          const isOver = dragOverStatus === status;

          return (
            <div
              key={status}
              className={cn(
                "flex flex-col rounded-lg transition-colors",
                isOver && "ring-2 ring-primary ring-offset-2"
              )}
              onDragOver={(e) => handleDragOver(e, status)}
              onDrop={(e) => handleDrop(e, status)}
            >
              <div
                className={cn(
                  "sticky top-0 z-10 rounded-t-lg p-3",
                  config.bgColor
                )}
              >
                <div className="flex items-center justify-between">
                  <span className={cn("text-sm font-medium", config.color)}>
                    {config.label}
                  </span>
                  <Badge variant="secondary" className="text-xs">
                    {stats?.count || 0}
                  </Badge>
                </div>
                {stats?.total_value ? (
                  <div className="mt-1 text-xs text-muted-foreground">
                    <CurrencyAmount amount={stats.total_value} currency="EUR" />
                  </div>
                ) : null}
              </div>

              <div className="flex-1 space-y-2 overflow-y-auto p-2">
                {leads.length === 0 ? (
                  <div className="flex h-20 items-center justify-center rounded border-2 border-dashed text-xs text-muted-foreground">
                    No leads
                  </div>
                ) : (
                  leads.map((lead) => (
                    <LeadCard
                      key={lead.id}
                      lead={lead}
                      isDragging={draggedLead?.id === lead.id}
                      onDragStart={() => handleDragStart(lead)}
                      onDragEnd={handleDragEnd}
                      onClick={() => onLeadClick?.(lead)}
                    />
                  ))
                )}
              </div>
            </div>
          );
        })}
      </div>
    </div>
  );
}

interface LeadCardProps {
  lead: Lead;
  isDragging: boolean;
  onDragStart: () => void;
  onDragEnd: () => void;
  onClick?: () => void;
}

function LeadCard({
  lead,
  isDragging,
  onDragStart,
  onDragEnd,
  onClick,
}: LeadCardProps) {
  const config = STATUS_CONFIG[lead.status];

  const handleKeyDown = (e: React.KeyboardEvent) => {
    if (e.key === "Enter" || e.key === " ") {
      e.preventDefault();
      onClick?.();
    }
  };

  return (
    <Card
      draggable
      onDragStart={onDragStart}
      onDragEnd={onDragEnd}
      className={cn(
        "cursor-grab transition-all active:cursor-grabbing",
        isDragging && "opacity-50",
        "hover:shadow-md"
      )}
    >
      <CardContent className="p-3">
        <div className="flex items-start gap-2">
          <GripVertical className="mt-0.5 h-4 w-4 shrink-0 text-muted-foreground opacity-50" />
          <div className="min-w-0 flex-1">
            <Link
              href={`/leads/${lead.id}`}
              className="block font-medium text-foreground hover:underline"
              onClick={(e) => {
                if (onClick) {
                  e.preventDefault();
                  onClick();
                }
              }}
              onKeyDown={handleKeyDown}
            >
              {lead.company_name || lead.full_name}
            </Link>

            {lead.company_name && (
              <p className="mt-0.5 truncate text-xs text-muted-foreground">
                {lead.full_name}
              </p>
            )}

            {lead.estimated_value && (
              <div className="mt-2 flex items-center gap-1 text-sm">
                <DollarSign className="h-3 w-3 text-muted-foreground" />
                <CurrencyAmount
                  amount={lead.estimated_value}
                  currency={lead.currency}
                />
              </div>
            )}

            <div className="mt-2 flex items-center justify-between">
              <Badge variant="outline" className="text-xs capitalize">
                {lead.source}
              </Badge>
              {lead.probability && (
                <div className="flex items-center gap-1 text-xs text-muted-foreground">
                  <TrendingUp className="h-3 w-3" />
                  {lead.probability}%
                </div>
              )}
            </div>
          </div>
        </div>
      </CardContent>
    </Card>
  );
}

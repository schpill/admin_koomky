"use client";

import Link from "next/link";
import { useEffect, useState } from "react";
import { toast } from "sonner";
import { Plus, LayoutGrid, List, Search } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Badge } from "@/components/ui/badge";
import { CurrencyAmount } from "@/components/shared/currency-amount";
import { useLeadStore } from "@/lib/stores/leads";
import { useI18n } from "@/components/providers/i18n-provider";

const STATUS_COLORS: Record<string, string> = {
  new: "bg-blue-100 text-blue-800",
  contacted: "bg-yellow-100 text-yellow-800",
  qualified: "bg-purple-100 text-purple-800",
  proposal_sent: "bg-orange-100 text-orange-800",
  negotiating: "bg-pink-100 text-pink-800",
  won: "bg-green-100 text-green-800",
  lost: "bg-red-100 text-red-800",
};

export default function LeadsPage() {
  const { t } = useI18n();
  const { leads, pipeline, isLoading, fetchLeads, fetchPipeline } =
    useLeadStore();

  const [viewMode, setViewMode] = useState<"kanban" | "list">("kanban");
  const [filters, setFilters] = useState<Record<string, string>>({});

  useEffect(() => {
    if (viewMode === "kanban") {
      fetchPipeline();
    } else {
      fetchLeads({ ...filters, per_page: 50 });
    }
  }, [fetchPipeline, fetchLeads, viewMode, filters]);

  const handleSearch = (search: string) => {
    setFilters((current) => ({ ...current, search }));
  };

  const columnOrder = [
    "new",
    "contacted",
    "qualified",
    "proposal_sent",
    "negotiating",
  ];

  return (
    <div className="space-y-6">
      <div className="flex flex-wrap items-center justify-between gap-3">
        <div>
          <h1 className="text-3xl font-bold">{t("leads.title")}</h1>
          <p className="text-sm text-muted-foreground">
            {pipeline?.total_pipeline_value
              ? t("leads.pipelineValue", {
                  value: `€${pipeline.total_pipeline_value.toLocaleString()}`,
                })
              : t("leads.subtitle")}
          </p>
        </div>
        <div className="flex items-center gap-2">
          <Button
            variant={viewMode === "kanban" ? "default" : "outline"}
            size="sm"
            onClick={() => setViewMode("kanban")}
          >
            <LayoutGrid className="mr-2 h-4 w-4" />
            {t("leads.kanban")}
          </Button>
          <Button
            variant={viewMode === "list" ? "default" : "outline"}
            size="sm"
            onClick={() => setViewMode("list")}
          >
            <List className="mr-2 h-4 w-4" />
            {t("leads.list")}
          </Button>
          <Button asChild>
            <Link href="/leads/create">
              <Plus className="mr-2 h-4 w-4" />
              {t("leads.addLead")}
            </Link>
          </Button>
        </div>
      </div>

      <Card>
        <CardContent className="pt-4">
          <div className="flex items-center gap-4">
            <div className="relative flex-1">
              <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
              <Input
                placeholder={t("leads.searchPlaceholder")}
                className="pl-10"
                value={filters.search || ""}
                onChange={(e) => handleSearch(e.target.value)}
              />
            </div>
            <select
              className="h-10 rounded-md border border-input bg-background px-3 text-sm"
              value={filters.status || ""}
              onChange={(e) =>
                setFilters((current) => ({
                  ...current,
                  status: e.target.value,
                }))
              }
            >
              <option value="">{t("leads.allStatuses")}</option>
              <option value="new">{t("leads.status.new")}</option>
              <option value="contacted">{t("leads.status.contacted")}</option>
              <option value="qualified">{t("leads.status.qualified")}</option>
              <option value="proposal_sent">
                {t("leads.status.proposal_sent")}
              </option>
              <option value="negotiating">
                {t("leads.status.negotiating")}
              </option>
              <option value="won">{t("leads.status.won")}</option>
              <option value="lost">{t("leads.status.lost")}</option>
            </select>
            <select
              className="h-10 rounded-md border border-input bg-background px-3 text-sm"
              value={filters.source || ""}
              onChange={(e) =>
                setFilters((current) => ({
                  ...current,
                  source: e.target.value,
                }))
              }
            >
              <option value="">{t("leads.allSources")}</option>
              <option value="manual">{t("leads.source.manual")}</option>
              <option value="referral">{t("leads.source.referral")}</option>
              <option value="website">{t("leads.source.website")}</option>
              <option value="campaign">{t("leads.source.campaign")}</option>
              <option value="other">{t("leads.source.other")}</option>
            </select>
          </div>
        </CardContent>
      </Card>

      {viewMode === "kanban" && pipeline ? (
        <div className="grid gap-4 md:grid-cols-3 lg:grid-cols-5">
          {columnOrder.map((status) => (
            <div key={status} className="space-y-3">
              <div className="flex items-center justify-between rounded-lg bg-muted p-2">
                <span className="text-sm font-medium capitalize">
                  {t(`leads.status.${status}`)}
                </span>
                <Badge variant="secondary">
                  {pipeline.column_stats[status]?.count || 0}
                </Badge>
              </div>
              <div className="space-y-2">
                {pipeline.columns[status]?.map((lead) => (
                  <Link key={lead.id} href={`/leads/${lead.id}`}>
                    <Card className="cursor-pointer transition-shadow hover:shadow-md">
                      <CardContent className="p-3">
                        <div className="mb-2 font-medium">
                          {lead.company_name || lead.full_name}
                        </div>
                        {lead.estimated_value && (
                          <div className="mb-2 text-sm text-muted-foreground">
                            <CurrencyAmount
                              amount={lead.estimated_value}
                              currency={lead.currency}
                            />
                          </div>
                        )}
                        <div className="flex items-center justify-between text-xs">
                          <span className="capitalize text-muted-foreground">
                            {lead.source}
                          </span>
                          {lead.probability && (
                            <Badge variant="outline">{lead.probability}%</Badge>
                          )}
                        </div>
                      </CardContent>
                    </Card>
                  </Link>
                ))}
              </div>
            </div>
          ))}
        </div>
      ) : (
        <Card>
          <CardContent className="p-0">
            {isLoading ? (
              <div className="p-4 text-center text-muted-foreground">
                {t("leads.loading")}
              </div>
            ) : leads.length === 0 ? (
              <div className="p-4 text-center text-muted-foreground">
                {t("leads.noLeads")}
              </div>
            ) : (
              <div className="overflow-x-auto">
                <table className="w-full text-sm">
                  <thead>
                    <tr className="border-b text-left">
                      <th className="p-4">{t("leads.table.name")}</th>
                      <th className="p-4">{t("leads.table.company")}</th>
                      <th className="p-4">{t("leads.table.status")}</th>
                      <th className="p-4">{t("leads.table.value")}</th>
                      <th className="p-4">{t("leads.table.probability")}</th>
                      <th className="p-4">{t("leads.table.closeDate")}</th>
                      <th className="p-4">{t("leads.table.source")}</th>
                    </tr>
                  </thead>
                  <tbody>
                    {leads.map((lead) => (
                      <tr key={lead.id} className="border-b last:border-0">
                        <td className="p-4">
                          <Link
                            href={`/leads/${lead.id}`}
                            className="font-medium text-primary hover:underline"
                          >
                            {lead.full_name}
                          </Link>
                        </td>
                        <td className="p-4 text-muted-foreground">
                          {lead.company_name || "-"}
                        </td>
                        <td className="p-4">
                          <Badge className={STATUS_COLORS[lead.status]}>
                            {t(`leads.status.${lead.status}`)}
                          </Badge>
                        </td>
                        <td className="p-4">
                          {lead.estimated_value ? (
                            <CurrencyAmount
                              amount={lead.estimated_value}
                              currency={lead.currency}
                            />
                          ) : (
                            "-"
                          )}
                        </td>
                        <td className="p-4">
                          {lead.probability ? `${lead.probability}%` : "-"}
                        </td>
                        <td className="p-4 text-muted-foreground">
                          {lead.expected_close_date || "-"}
                        </td>
                        <td className="p-4 capitalize text-muted-foreground">
                          {lead.source}
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            )}
          </CardContent>
        </Card>
      )}
    </div>
  );
}

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
          <h1 className="text-3xl font-bold">{t("leads.title") || "Leads"}</h1>
          <p className="text-sm text-muted-foreground">
            {pipeline?.total_pipeline_value
              ? `Pipeline Value: €${pipeline.total_pipeline_value.toLocaleString()}`
              : "Manage your sales pipeline"}
          </p>
        </div>
        <div className="flex items-center gap-2">
          <Button
            variant={viewMode === "kanban" ? "default" : "outline"}
            size="sm"
            onClick={() => setViewMode("kanban")}
          >
            <LayoutGrid className="mr-2 h-4 w-4" />
            Kanban
          </Button>
          <Button
            variant={viewMode === "list" ? "default" : "outline"}
            size="sm"
            onClick={() => setViewMode("list")}
          >
            <List className="mr-2 h-4 w-4" />
            List
          </Button>
          <Button asChild>
            <Link href="/leads/create">
              <Plus className="mr-2 h-4 w-4" />
              {t("leads.addLead") || "Add Lead"}
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
                placeholder="Search leads..."
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
              <option value="">All Statuses</option>
              <option value="new">New</option>
              <option value="contacted">Contacted</option>
              <option value="qualified">Qualified</option>
              <option value="proposal_sent">Proposal Sent</option>
              <option value="negotiating">Negotiating</option>
              <option value="won">Won</option>
              <option value="lost">Lost</option>
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
              <option value="">All Sources</option>
              <option value="manual">Manual</option>
              <option value="referral">Referral</option>
              <option value="website">Website</option>
              <option value="campaign">Campaign</option>
              <option value="other">Other</option>
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
                  {status.replace("_", " ")}
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
                Loading...
              </div>
            ) : leads.length === 0 ? (
              <div className="p-4 text-center text-muted-foreground">
                No leads found
              </div>
            ) : (
              <div className="overflow-x-auto">
                <table className="w-full text-sm">
                  <thead>
                    <tr className="border-b text-left">
                      <th className="p-4">Name</th>
                      <th className="p-4">Company</th>
                      <th className="p-4">Status</th>
                      <th className="p-4">Value</th>
                      <th className="p-4">Probability</th>
                      <th className="p-4">Close Date</th>
                      <th className="p-4">Source</th>
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
                            {lead.status.replace("_", " ")}
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

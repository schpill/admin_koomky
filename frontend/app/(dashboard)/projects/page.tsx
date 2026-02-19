"use client";

import { useEffect, useMemo, useState } from "react";
import Link from "next/link";
import { Plus, FolderKanban } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { EmptyState } from "@/components/ui/empty-state";
import { Skeleton } from "@/components/ui/skeleton";
import { ProjectFilterBar } from "@/components/projects/project-filter-bar";
import { useProjectStore } from "@/lib/stores/projects";
import { useClientStore } from "@/lib/stores/clients";
import { useI18n } from "@/components/providers/i18n-provider";

function statusVariant(
  status: string
): "default" | "secondary" | "destructive" {
  if (status === "completed") {
    return "default";
  }

  if (status === "cancelled") {
    return "destructive";
  }

  return "secondary";
}

export default function ProjectsPage() {
  const { t } = useI18n();
  const { projects, isLoading, fetchProjects, pagination } = useProjectStore();
  const { clients, fetchClients } = useClientStore();
  const [filters, setFilters] = useState<Record<string, unknown>>({
    sort_by: "created_at",
    sort_order: "desc",
  });

  useEffect(() => {
    fetchClients();
  }, [fetchClients]);

  useEffect(() => {
    fetchProjects(filters);
  }, [fetchProjects, filters]);

  const totalLabel = useMemo(() => {
    if (!pagination) {
      return "";
    }

    return t("projects.totalCount", { count: pagination.total });
  }, [pagination, t]);

  return (
    <div className="space-y-6">
      <div className="flex flex-wrap items-center justify-between gap-3">
        <div>
          <h1 className="text-3xl font-bold">{t("projects.title")}</h1>
          <p className="text-sm text-muted-foreground">{totalLabel}</p>
        </div>
        <Button asChild>
          <Link href="/projects/create">
            <Plus className="mr-2 h-4 w-4" />
            {t("projects.newProject")}
          </Link>
        </Button>
      </div>

      <ProjectFilterBar
        clients={clients.map((client) => ({
          id: client.id,
          name: client.name,
        }))}
        onApply={(nextFilters) =>
          setFilters({
            ...filters,
            ...nextFilters,
          })
        }
      />

      <Card>
        <CardHeader>
          <CardTitle>{t("projects.projectList")}</CardTitle>
        </CardHeader>
        <CardContent>
          {isLoading && projects.length === 0 ? (
            <div className="space-y-3">
              <Skeleton className="h-12 w-full" />
              <Skeleton className="h-12 w-full" />
              <Skeleton className="h-12 w-full" />
            </div>
          ) : projects.length === 0 ? (
            <EmptyState
              icon={<FolderKanban className="h-12 w-12" />}
              title={t("projects.empty.title")}
              description={t("projects.empty.description")}
              action={
                <Button asChild>
                  <Link href="/projects/create">
                    {t("projects.empty.action")}
                  </Link>
                </Button>
              }
            />
          ) : (
            <div className="overflow-x-auto">
              <table className="w-full text-sm">
                <thead>
                  <tr className="border-b text-left">
                    <th className="pb-3 font-medium text-muted-foreground">
                      {t("projects.table.reference")}
                    </th>
                    <th className="pb-3 font-medium text-muted-foreground">
                      {t("projects.table.project")}
                    </th>
                    <th className="pb-3 font-medium text-muted-foreground">
                      {t("projects.table.client")}
                    </th>
                    <th className="pb-3 font-medium text-muted-foreground">
                      {t("projects.table.status")}
                    </th>
                    <th className="pb-3 font-medium text-muted-foreground">
                      {t("projects.table.deadline")}
                    </th>
                    <th className="pb-3 font-medium text-muted-foreground">
                      {t("projects.table.progress")}
                    </th>
                  </tr>
                </thead>
                <tbody>
                  {projects.map((project) => {
                    const progress = Math.round(
                      project.progress_percentage ?? 0
                    );
                    return (
                      <tr
                        key={project.id}
                        className="border-b last:border-0 hover:bg-muted/30"
                      >
                        <td className="py-4 font-mono text-xs">
                          {project.reference || "-"}
                        </td>
                        <td className="py-4">
                          <Link
                            href={`/projects/${project.id}`}
                            className="font-medium text-primary hover:underline"
                          >
                            {project.name}
                          </Link>
                        </td>
                        <td className="py-4 text-muted-foreground">
                          {project.client?.name || "-"}
                        </td>
                        <td className="py-4">
                          <Badge
                            variant={statusVariant(project.status)}
                            className="capitalize"
                          >
                            {project.status.replace("_", " ")}
                          </Badge>
                        </td>
                        <td className="py-4 text-muted-foreground">
                          {project.deadline || "-"}
                        </td>
                        <td className="py-4">
                          <div className="w-36 space-y-1">
                            <div className="h-2 rounded-full bg-muted">
                              <div
                                className="h-2 rounded-full bg-primary"
                                style={{ width: `${progress}%` }}
                              />
                            </div>
                            <p className="text-xs text-muted-foreground">
                              {progress}%
                            </p>
                          </div>
                        </td>
                      </tr>
                    );
                  })}
                </tbody>
              </table>
            </div>
          )}
        </CardContent>
      </Card>
    </div>
  );
}

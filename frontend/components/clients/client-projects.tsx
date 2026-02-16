"use client";

import { useEffect } from "react";
import Link from "next/link";
import { FolderKanban } from "lucide-react";
import { useProjectStore } from "@/lib/stores/projects";
import { EmptyState } from "@/components/ui/empty-state";
import { Badge } from "@/components/ui/badge";
import { Skeleton } from "@/components/ui/skeleton";

interface ClientProjectsProps {
  clientId: string;
}

export function ClientProjects({ clientId }: ClientProjectsProps) {
  const { projects, isLoading, fetchProjects } = useProjectStore();

  useEffect(() => {
    fetchProjects({ client_id: clientId, per_page: 50 });
  }, [fetchProjects, clientId]);

  if (isLoading && projects.length === 0) {
    return (
      <div className="space-y-2">
        <Skeleton className="h-12 w-full" />
        <Skeleton className="h-12 w-full" />
      </div>
    );
  }

  if (projects.length === 0) {
    return (
      <EmptyState
        icon={<FolderKanban className="h-10 w-10" />}
        title="No projects for this client"
        description="Create a project and link it to this client to track delivery and billing."
      />
    );
  }

  return (
    <div className="overflow-hidden rounded-lg border">
      <table className="w-full text-sm">
        <thead className="bg-muted/50 text-left">
          <tr>
            <th className="px-4 py-3 font-medium">Reference</th>
            <th className="px-4 py-3 font-medium">Project</th>
            <th className="px-4 py-3 font-medium">Status</th>
            <th className="px-4 py-3 font-medium">Progress</th>
          </tr>
        </thead>
        <tbody>
          {projects.map((project) => {
            const progress = Math.round(project.progress_percentage ?? 0);
            return (
              <tr key={project.id} className="border-t">
                <td className="px-4 py-3 font-mono text-xs">{project.reference || "-"}</td>
                <td className="px-4 py-3">
                  <Link href={`/projects/${project.id}`} className="font-medium text-primary hover:underline">
                    {project.name}
                  </Link>
                </td>
                <td className="px-4 py-3">
                  <Badge variant={project.status === "completed" ? "default" : "secondary"}>
                    {project.status.replace("_", " ")}
                  </Badge>
                </td>
                <td className="px-4 py-3">{progress}%</td>
              </tr>
            );
          })}
        </tbody>
      </table>
    </div>
  );
}

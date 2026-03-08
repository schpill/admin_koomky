import { promises as fs } from "fs";
import path from "path";
import type { ComponentType } from "react";

import GettingStartedPage from "@/content/docs/getting-started/index.mdx";
import DashboardPage from "@/content/docs/dashboard/index.mdx";
import ClientsPage from "@/content/docs/clients/index.mdx";
import LeadsPage from "@/content/docs/leads/index.mdx";
import InvoicesPage from "@/content/docs/invoices/index.mdx";
import InvoicesLifecyclePage from "@/content/docs/invoices/lifecycle.mdx";
import QuotesPage from "@/content/docs/quotes/index.mdx";
import CreditNotesPage from "@/content/docs/credit-notes/index.mdx";
import ExpensesPage from "@/content/docs/expenses/index.mdx";
import ProjectsPage from "@/content/docs/projects/index.mdx";
import CalendarPage from "@/content/docs/calendar/index.mdx";
import CampaignsPage from "@/content/docs/campaigns/index.mdx";
import CampaignsAbTestingPage from "@/content/docs/campaigns/ab-testing.mdx";
import DripPage from "@/content/docs/drip/index.mdx";
import WorkflowsPage from "@/content/docs/workflows/index.mdx";
import SuppressionPage from "@/content/docs/suppression/index.mdx";
import DocumentsPage from "@/content/docs/documents/index.mdx";
import TicketsPage from "@/content/docs/tickets/index.mdx";
import RagPage from "@/content/docs/rag/index.mdx";
import RagMcpPage from "@/content/docs/rag/mcp.mdx";
import PortalPage from "@/content/docs/portal/index.mdx";
import RemindersPage from "@/content/docs/reminders/index.mdx";
import WarmupPage from "@/content/docs/warmup/index.mdx";
import ScoringPage from "@/content/docs/scoring/index.mdx";
import SettingsPage from "@/content/docs/settings/index.mdx";

export const docsContentBySlug: Record<string, ComponentType> = {
  "getting-started": GettingStartedPage,
  dashboard: DashboardPage,
  clients: ClientsPage,
  leads: LeadsPage,
  invoices: InvoicesPage,
  "invoices/lifecycle": InvoicesLifecyclePage,
  quotes: QuotesPage,
  "credit-notes": CreditNotesPage,
  expenses: ExpensesPage,
  projects: ProjectsPage,
  calendar: CalendarPage,
  campaigns: CampaignsPage,
  "campaigns/ab-testing": CampaignsAbTestingPage,
  drip: DripPage,
  workflows: WorkflowsPage,
  suppression: SuppressionPage,
  documents: DocumentsPage,
  tickets: TicketsPage,
  rag: RagPage,
  "rag/mcp": RagMcpPage,
  portal: PortalPage,
  reminders: RemindersPage,
  warmup: WarmupPage,
  scoring: ScoringPage,
  settings: SettingsPage,
};

export async function readDocSource(slug: string) {
  const basePath = path.join(process.cwd(), "content", "docs", ...slug.split("/"));
  const candidates = [`${basePath}.mdx`, path.join(basePath, "index.mdx")];

  for (const candidate of candidates) {
    try {
      return await fs.readFile(candidate, "utf8");
    } catch {
      continue;
    }
  }

  return null;
}

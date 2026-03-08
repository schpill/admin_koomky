import type { LucideIcon } from "lucide-react";
import {
  BookOpen,
  LayoutDashboard,
  Users,
  Target,
  FileText,
  Receipt,
  FolderKanban,
  CalendarDays,
  Megaphone,
  GitBranch,
  Workflow,
  Ban,
  Brain,
  LifeBuoy,
  ShieldCheck,
  Bell,
  Gauge,
  Settings,
  Sparkles,
} from "lucide-react";

export type DocModuleMeta = {
  title: string;
  slug: string;
  description: string;
  category: "core" | "growth" | "operations";
  icon: LucideIcon;
};

export const DOC_MODULES: DocModuleMeta[] = [
  {
    title: "Démarrage rapide",
    slug: "getting-started",
    description: "Prendre en main Koomky, configurer le socle et réaliser le premier cycle client.",
    category: "core",
    icon: Sparkles,
  },
  {
    title: "Tableau de bord",
    slug: "dashboard",
    description: "Lire les indicateurs, surveiller les alertes et piloter l'activite quotidienne.",
    category: "core",
    icon: LayoutDashboard,
  },
  {
    title: "Clients",
    slug: "clients",
    description: "Structurer le portefeuille clients, les contacts et l'historique commercial.",
    category: "core",
    icon: Users,
  },
  {
    title: "Prospects & Leads",
    slug: "leads",
    description: "Capturer, qualifier, scorer et convertir les opportunites en clients facturables.",
    category: "core",
    icon: Target,
  },
  {
    title: "Factures",
    slug: "invoices",
    description: "Creer, envoyer, relancer et suivre le cycle de vie complet des factures.",
    category: "core",
    icon: FileText,
  },
  {
    title: "Devis",
    slug: "quotes",
    description: "Produire des propositions commerciales, suivre l'acceptation et convertir en facture.",
    category: "core",
    icon: FileText,
  },
  {
    title: "Avoirs",
    slug: "credit-notes",
    description: "Ger er les corrections, remboursements et impacts comptables sur les factures.",
    category: "core",
    icon: FileText,
  },
  {
    title: "Dépenses",
    slug: "expenses",
    description: "Centraliser les frais, les justificatifs et l'impact sur la marge projet.",
    category: "core",
    icon: Receipt,
  },
  {
    title: "Projets",
    slug: "projects",
    description: "Suivre l'avancement, la rentabilite et la liaison entre production et facturation.",
    category: "core",
    icon: FolderKanban,
  },
  {
    title: "Calendrier",
    slug: "calendar",
    description: "Synchroniser les agendas et piloter les evenements automatiques relies au CRM.",
    category: "core",
    icon: CalendarDays,
  },
  {
    title: "Campagnes email",
    slug: "campaigns",
    description: "Concevoir, segmenter, envoyer et analyser les campagnes email a grande echelle.",
    category: "growth",
    icon: Megaphone,
  },
  {
    title: "Drip campaigns",
    slug: "drip",
    description: "Orchestrer des sequences multi-etapes basees sur le comportement des contacts.",
    category: "growth",
    icon: GitBranch,
  },
  {
    title: "Workflows automatisés",
    slug: "workflows",
    description: "Automatiser les parcours, les relances et les actions metier par noeuds.",
    category: "growth",
    icon: Workflow,
  },
  {
    title: "Liste de suppression",
    slug: "suppression",
    description: "Controler les exclusions, desabonnements et protections de reputation d'envoi.",
    category: "growth",
    icon: Ban,
  },
  {
    title: "Documents",
    slug: "documents",
    description: "Stocker, qualifier, retrouver et diffuser les documents operationnels et clients.",
    category: "operations",
    icon: BookOpen,
  },
  {
    title: "Tickets support",
    slug: "tickets",
    description: "Traiter les demandes, assignations et SLA avec historique conversationnel.",
    category: "operations",
    icon: LifeBuoy,
  },
  {
    title: "Intelligence documentaire",
    slug: "rag",
    description: "Questionner la base documentaire, suivre les embeddings et l'usage MCP associe.",
    category: "operations",
    icon: Brain,
  },
  {
    title: "Portail client",
    slug: "portal",
    description: "Offrir un espace securise pour consulter, payer et valider les documents clients.",
    category: "operations",
    icon: ShieldCheck,
  },
  {
    title: "Relances automatiques",
    slug: "reminders",
    description: "Cadencer les relances de facturation pour reduire le retard d'encaissement.",
    category: "operations",
    icon: Bell,
  },
  {
    title: "Warm-up IP",
    slug: "warmup",
    description: "Piloter la montee en charge d'envoi et securiser la delivrabilite.",
    category: "growth",
    icon: Gauge,
  },
  {
    title: "Scoring leads",
    slug: "scoring",
    description: "Attribuer des scores, prioriser les leads et outiller les segments intelligents.",
    category: "growth",
    icon: Gauge,
  },
  {
    title: "Paramètres",
    slug: "settings",
    description: "Configurer l'application, la securite, les integrations et les preferences metier.",
    category: "operations",
    icon: Settings,
  },
];

export const DOC_MODULES_BY_SLUG = Object.fromEntries(
  DOC_MODULES.map((module) => [module.slug, module])
) as Record<string, DocModuleMeta>;

export const DOC_GROUPS = [
  { key: "core", label: "Essentiels CRM" },
  { key: "growth", label: "Automatisation & croissance" },
  { key: "operations", label: "Operations & support" },
] as const;

export function getDocHref(slug: string) {
  return `/docs/${slug}`;
}

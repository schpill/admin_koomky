export const API_SCOPES = [
  { name: "read:clients", description: "Read client information" },
  {
    name: "write:clients",
    description: "Create, update, and delete clients",
  },
  { name: "read:invoices", description: "Read invoices and credit notes" },
  {
    name: "write:invoices",
    description: "Create, update, and delete invoices",
  },
  { name: "read:expenses", description: "Read expense records" },
  {
    name: "write:expenses",
    description: "Create, update, and delete expenses",
  },
  { name: "read:projects", description: "Read project information" },
  { name: "read:leads", description: "Read lead information" },
  {
    name: "write:leads",
    description: "Create, update, and delete leads",
  },
  { name: "read:reports", description: "Read reports and analytics" },
  { name: "read:quotes", description: "Read quotes" },
  { name: "write:quotes", description: "Create, update, and delete quotes" },
] as const;

export type ApiScope = (typeof API_SCOPES)[number]["name"];

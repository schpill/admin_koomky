import { Badge } from "@/components/ui/badge";

type DocBadgeProps = {
  children: React.ReactNode;
  variant?: "default" | "secondary" | "destructive" | "outline";
};

export function DocBadge({ children, variant = "secondary" }: DocBadgeProps) {
  return <Badge variant={variant}>{children}</Badge>;
}

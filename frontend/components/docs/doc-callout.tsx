import type { ReactNode } from "react";
import { AlertTriangle, Info, Lightbulb, ShieldAlert } from "lucide-react";
import { cn } from "@/lib/utils";

const variants = {
  info: {
    icon: Info,
    className: "border-sky-500/40 bg-sky-500/10 text-sky-950",
    darkClassName: "dark:text-sky-100",
  },
  tip: {
    icon: Lightbulb,
    className: "border-emerald-500/40 bg-emerald-500/10 text-emerald-950",
    darkClassName: "dark:text-emerald-100",
  },
  warning: {
    icon: AlertTriangle,
    className: "border-amber-500/40 bg-amber-500/10 text-amber-950",
    darkClassName: "dark:text-amber-100",
  },
  danger: {
    icon: ShieldAlert,
    className: "border-rose-500/40 bg-rose-500/10 text-rose-950",
    darkClassName: "dark:text-rose-100",
  },
} as const;

type DocCalloutProps = {
  type?: keyof typeof variants;
  title?: string;
  children: ReactNode;
};

export function DocCallout({
  type = "info",
  title,
  children,
}: DocCalloutProps) {
  const variant = variants[type];
  const Icon = variant.icon;

  return (
    <aside
      className={cn(
        "my-6 flex gap-3 rounded-2xl border px-4 py-4 shadow-sm",
        variant.className,
        variant.darkClassName
      )}
    >
      <Icon className="mt-0.5 h-5 w-5 shrink-0" />
      <div className="space-y-1 text-sm leading-6">
        {title ? <p className="font-semibold">{title}</p> : null}
        <div>{children}</div>
      </div>
    </aside>
  );
}

import {
  FileText,
  FileSpreadsheet,
  FileImage,
  FileArchive,
  FileCode,
  File,
  FilePieChart,
  FileTerminal,
  type LucideIcon,
} from "lucide-react";
import { Badge } from "@/components/ui/badge";
import { cn } from "@/lib/utils";
import type { DocumentType } from "@/lib/stores/documents";

interface DocumentTypeBadgeProps {
  type: DocumentType;
  scriptLanguage?: string | null;
  className?: string;
  showLabel?: boolean;
}

const typeConfigs: Record<
  DocumentType,
  { icon: LucideIcon; label: string; color: string }
> = {
  pdf: {
    icon: FileText,
    label: "PDF",
    color: "bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400",
  },
  spreadsheet: {
    icon: FileSpreadsheet,
    label: "Spreadsheet",
    color:
      "bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400",
  },
  document: {
    icon: File,
    label: "Document",
    color: "bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400",
  },
  text: {
    icon: FileText,
    label: "Text",
    color: "bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-400",
  },
  script: {
    icon: FileCode,
    label: "Script",
    color:
      "bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400",
  },
  image: {
    icon: FileImage,
    label: "Image",
    color:
      "bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400",
  },
  archive: {
    icon: FileArchive,
    label: "Archive",
    color:
      "bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400",
  },
  presentation: {
    icon: FilePieChart,
    label: "Presentation",
    color: "bg-teal-100 text-teal-700 dark:bg-teal-900/30 dark:text-teal-400",
  },
  other: {
    icon: File,
    label: "Other",
    color: "bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-400",
  },
};

export function DocumentTypeBadge({
  type,
  scriptLanguage,
  className,
  showLabel = true,
}: DocumentTypeBadgeProps) {
  const config = typeConfigs[type] || typeConfigs.other;
  const Icon = config.icon;

  return (
    <Badge
      variant="secondary"
      className={cn(
        "flex items-center gap-1.5 font-medium",
        config.color,
        className
      )}
    >
      <Icon className="h-3.5 w-3.5" />
      {showLabel && (
        <span>
          {type === "script" && scriptLanguage ? scriptLanguage : config.label}
        </span>
      )}
    </Badge>
  );
}

"use client";

import * as React from "react";
import { useRouter } from "next/navigation";
import {
  Calculator,
  Settings,
  User,
  Search,
} from "lucide-react";
import { Command } from "cmdk";
import { Dialog, DialogContent } from "@/components/ui/dialog";
import { apiClient } from "@/lib/api";
import { useDebounce } from "use-debounce";
import { useI18n } from "@/components/providers/i18n-provider";

export function CommandPalette() {
  const [open, setOpen] = React.useState(false);
  const [query, setQuery] = React.useState("");
  const [debouncedQuery] = useDebounce(query, 300);
  const [results, setResults] = React.useState<any[]>([]);
  const [isLoading, setIsLoading] = React.useState(false);
  const router = useRouter();
  const { t } = useI18n();

  React.useEffect(() => {
    const down = (e: KeyboardEvent) => {
      if (e.key === "k" && (e.metaKey || e.ctrlKey)) {
        e.preventDefault();
        setOpen((open) => !open);
      }
    };

    document.addEventListener("keydown", down);
    return () => document.removeEventListener("keydown", down);
  }, []);

  React.useEffect(() => {
    if (!debouncedQuery) {
      setResults([]);
      return;
    }

    const search = async () => {
      setIsLoading(true);
      try {
        const response = await apiClient.get<any>(
          `/search?q=${debouncedQuery}`,
        );
        setResults(response.data.clients || []);
      } catch (error) {
        console.error("Search failed", error);
      } finally {
        setIsLoading(false);
      }
    };

    search();
  }, [debouncedQuery]);

  const runCommand = React.useCallback((command: () => void) => {
    setOpen(false);
    command();
  }, []);

  return (
    <>
      <button
        onClick={() => setOpen(true)}
        className="brand-control flex h-9 w-80 items-center justify-between rounded-md px-3 text-sm text-muted-foreground transition-colors hover:bg-accent/80 hover:text-accent-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
      >
        <div className="flex items-center gap-2">
          <Search className="h-4 w-4" />
          <span>{t("commandPalette.searchTrigger")}</span>
        </div>
        <kbd className="pointer-events-none inline-flex h-5 select-none items-center gap-1 rounded border bg-muted px-1.5 font-mono text-[10px] font-medium text-muted-foreground opacity-100">
          <span className="text-xs">⌘</span>K
        </kbd>
      </button>

      <Dialog open={open} onOpenChange={setOpen}>
        <DialogContent className="brand-glass overflow-hidden p-0 shadow-2xl shadow-primary/20">
          <Command className="flex h-full w-full flex-col overflow-hidden rounded-md bg-popover/90 text-popover-foreground">
            <div
              className="flex items-center border-b border-border/70 px-3"
              cmdk-input-wrapper=""
            >
              <Search className="mr-2 h-4 w-4 shrink-0 opacity-50" />
              <Command.Input
                placeholder={t("commandPalette.searchPlaceholder")}
                onValueChange={setQuery}
                className="flex h-11 w-full rounded-md bg-transparent py-3 text-sm outline-none placeholder:text-muted-foreground disabled:cursor-not-allowed disabled:opacity-50"
              />
            </div>
            <Command.List className="max-h-[300px] overflow-y-auto overflow-x-hidden p-2">
              <Command.Empty className="py-6 text-center text-sm">
                {isLoading
                  ? t("common.loading")
                  : t("commandPalette.noResults")}
              </Command.Empty>

              {results.length > 0 && (
                <Command.Group heading={t("commandPalette.clientsGroup")}>
                  {results.map((client) => (
                    <Command.Item
                      key={client.id}
                      value={client.name}
                      onSelect={() =>
                        runCommand(() => router.push(`/clients/${client.id}`))
                      }
                      className="relative flex cursor-default select-none items-center rounded-sm px-2 py-1.5 text-sm outline-none aria-selected:bg-accent aria-selected:text-accent-foreground data-[disabled]:pointer-events-none data-[disabled]:opacity-50"
                    >
                      <User className="mr-2 h-4 w-4" />
                      <span>{client.name}</span>
                      <span className="ml-2 text-xs text-muted-foreground">
                        {client.reference}
                      </span>
                    </Command.Item>
                  ))}
                </Command.Group>
              )}

              <Command.Separator className="-mx-1 h-px bg-border" />

              <Command.Group heading={t("commandPalette.quickLinksGroup")}>
                <Command.Item
                  onSelect={() => runCommand(() => router.push("/"))}
                  className="relative flex cursor-default select-none items-center rounded-sm px-2 py-1.5 text-sm outline-none aria-selected:bg-accent aria-selected:text-accent-foreground"
                >
                  <Calculator className="mr-2 h-4 w-4" />
                  <span>{t("commandPalette.dashboard")}</span>
                </Command.Item>
                <Command.Item
                  onSelect={() =>
                    runCommand(() => router.push("/settings/profile"))
                  }
                  className="relative flex cursor-default select-none items-center rounded-sm px-2 py-1.5 text-sm outline-none aria-selected:bg-accent aria-selected:text-accent-foreground"
                >
                  <Settings className="mr-2 h-4 w-4" />
                  <span>{t("commandPalette.settings")}</span>
                </Command.Item>
              </Command.Group>
            </Command.List>
          </Command>
        </DialogContent>
      </Dialog>
    </>
  );
}

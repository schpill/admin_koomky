"use client";

import { useEffect, useState } from "react";
import { Timer, ChevronDown } from "lucide-react";
import { Button } from "@/components/ui/button";
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";
import { useTimerStore } from "@/lib/stores/timer";
import { TimerDropdown } from "./timer-dropdown";

function formatElapsedTime(seconds: number): string {
  const hours = Math.floor(seconds / 3600);
  const minutes = Math.floor((seconds % 3600) / 60);
  const secs = seconds % 60;

  return `${hours.toString().padStart(2, "0")}:${minutes.toString().padStart(2, "0")}:${secs.toString().padStart(2, "0")}`;
}

export function TimerBadge() {
  const { activeEntry, isRunning, elapsedSeconds, tick, fetchActive } =
    useTimerStore();
  const [mounted, setMounted] = useState(false);
  const formattedElapsedTime = formatElapsedTime(elapsedSeconds);

  useEffect(() => {
    setMounted(true);
    fetchActive();
  }, [fetchActive]);

  useEffect(() => {
    if (!isRunning) return;

    const interval = setInterval(() => {
      tick();
    }, 1000);

    return () => clearInterval(interval);
  }, [isRunning, tick]);

  // Polling every 30 seconds to sync with server
  useEffect(() => {
    if (!isRunning) return;

    const pollInterval = setInterval(() => {
      fetchActive();
    }, 30000);

    return () => clearInterval(pollInterval);
  }, [isRunning, fetchActive]);

  if (!mounted || !isRunning) {
    return null;
  }

  return (
    <DropdownMenu>
      <DropdownMenuTrigger asChild>
        <Button
          variant="ghost"
          size="sm"
          className="relative flex items-center gap-2 text-red-600 hover:text-red-700 hover:bg-red-50 dark:hover:bg-red-950"
          aria-label={`Timer actif ${formattedElapsedTime} ${activeEntry?.task_name ?? ""}`.trim()}
        >
          <div className="relative flex h-8 w-8 items-center justify-center rounded-full bg-red-100 dark:bg-red-900">
            <Timer className="h-4 w-4 animate-pulse text-red-600 dark:text-red-400" />
          </div>
          <span className="font-mono text-sm font-medium">
            {formattedElapsedTime}
          </span>
          <ChevronDown className="h-4 w-4" />
        </Button>
      </DropdownMenuTrigger>
      <DropdownMenuContent align="end" className="w-80">
        <TimerDropdown />
      </DropdownMenuContent>
    </DropdownMenu>
  );
}

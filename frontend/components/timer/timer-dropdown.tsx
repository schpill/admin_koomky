"use client";

import { useState } from "react";
import { Play, Square, X, Clock } from "lucide-react";
import { Button } from "@/components/ui/button";
import { useTimerStore } from "@/lib/stores/timer";
import { toast } from "sonner";

function formatElapsedTime(seconds: number): string {
  const hours = Math.floor(seconds / 3600);
  const minutes = Math.floor((seconds % 3600) / 60);
  const secs = seconds % 60;

  return `${hours.toString().padStart(2, "0")}:${minutes.toString().padStart(2, "0")}:${secs.toString().padStart(2, "0")}`;
}

export function TimerDropdown() {
  const { activeEntry, elapsedSeconds, isLoading, stopTimer, cancelTimer } = useTimerStore();
  const [isStopping, setIsStopping] = useState(false);
  const [isCancelling, setIsCancelling] = useState(false);

  if (!activeEntry) {
    return (
      <div className="p-4 text-center text-sm text-muted-foreground">
        <Clock className="mx-auto mb-2 h-8 w-8 opacity-50" />
        <p>Aucun timer actif</p>
      </div>
    );
  }

  const handleStop = async () => {
    setIsStopping(true);
    try {
      await stopTimer();
      toast.success("Timer arrêté et temps enregistré");
    } catch (error) {
      toast.error("Erreur lors de l'arrêt du timer");
    } finally {
      setIsStopping(false);
    }
  };

  const handleCancel = async () => {
    setIsCancelling(true);
    try {
      await cancelTimer();
      toast.success("Timer annulé");
    } catch (error) {
      toast.error("Erreur lors de l'annulation du timer");
    } finally {
      setIsCancelling(false);
    }
  };

  return (
    <div className="p-4">
      <div className="mb-4 rounded-lg border bg-card p-3">
        <div className="flex items-center justify-between">
          <div>
            <p className="font-medium">{activeEntry.task_name}</p>
            <p className="text-sm text-muted-foreground">{activeEntry.project_name}</p>
          </div>
          <div className="text-right">
            <p className="font-mono text-2xl font-bold text-red-600">
              {formatElapsedTime(elapsedSeconds)}
            </p>
          </div>
        </div>
        {activeEntry.description && (
          <p className="mt-2 text-sm text-muted-foreground">{activeEntry.description}</p>
        )}
      </div>

      <div className="flex gap-2">
        <Button
          variant="default"
          className="flex-1 bg-green-600 hover:bg-green-700"
          onClick={handleStop}
          disabled={isStopping || isCancelling}
        >
          <Square className="mr-2 h-4 w-4" />
          {isStopping ? "Arrêt..." : "Arrêter"}
        </Button>
        <Button
          variant="outline"
          className="text-red-600 hover:text-red-700 hover:bg-red-50"
          onClick={handleCancel}
          disabled={isStopping || isCancelling}
        >
          <X className="mr-2 h-4 w-4" />
          {isCancelling ? "Annulation..." : "Annuler"}
        </Button>
      </div>
    </div>
  );
}
"use client";

import { Play, Square } from "lucide-react";
import { Button } from "@/components/ui/button";
import { useTimerStore } from "@/lib/stores/timer";
import { toast } from "sonner";
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from "@/components/ui/tooltip";

interface TaskTimerButtonProps {
  taskId: string;
  projectId: string;
  taskName: string;
}

export function TaskTimerButton({ taskId, projectId, taskName }: TaskTimerButtonProps) {
  const { isRunning, taskId: activeTaskId, startTimer, stopTimer, isLoading } = useTimerStore();

  const isThisTaskActive = isRunning && activeTaskId === taskId;
  const isOtherTaskActive = isRunning && activeTaskId !== taskId;

  const handleClick = async () => {
    if (isThisTaskActive) {
      try {
        await stopTimer();
        toast.success("Timer arrêté");
      } catch (error) {
        toast.error("Erreur lors de l'arrêt du timer");
      }
    } else if (!isOtherTaskActive) {
      try {
        await startTimer(taskId, projectId);
        toast.success(`Timer démarré sur "${taskName}"`);
      } catch (error) {
        toast.error("Erreur lors du démarrage du timer");
      }
    }
  };

  const button = (
    <Button
      variant={isThisTaskActive ? "destructive" : "outline"}
      size="sm"
      className={`h-8 w-8 p-0 ${isThisTaskActive ? "bg-red-600 hover:bg-red-700" : ""}`}
      onClick={handleClick}
      disabled={isLoading || isOtherTaskActive}
      title={isOtherTaskActive ? "Un timer est déjà actif sur une autre tâche" : undefined}
    >
      {isThisTaskActive ? (
        <Square className="h-4 w-4" />
      ) : (
        <Play className="h-4 w-4" />
      )}
    </Button>
  );

  if (isOtherTaskActive) {
    return (
      <TooltipProvider>
        <Tooltip>
          <TooltipTrigger asChild>{button}</TooltipTrigger>
          <TooltipContent>
            <p>Un timer est déjà actif sur une autre tâche</p>
          </TooltipContent>
        </Tooltip>
      </TooltipProvider>
    );
  }

  return button;
}
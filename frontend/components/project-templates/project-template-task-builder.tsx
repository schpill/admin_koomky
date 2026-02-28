"use client";

import {
  DndContext,
  PointerSensor,
  closestCenter,
  useSensor,
  useSensors,
  type DragEndEvent,
} from "@dnd-kit/core";
import {
  SortableContext,
  arrayMove,
  useSortable,
  verticalListSortingStrategy,
} from "@dnd-kit/sortable";
import { CSS } from "@dnd-kit/utilities";
import { GripVertical, Trash2 } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";

export interface ProjectTemplateTaskInput {
  id: string;
  title: string;
  description: string;
  estimated_hours: number | null;
  priority: "low" | "medium" | "high" | "urgent";
  sort_order: number;
}

interface ProjectTemplateTaskBuilderProps {
  value: ProjectTemplateTaskInput[];
  onChange: (tasks: ProjectTemplateTaskInput[]) => void;
}

function createTask(index: number): ProjectTemplateTaskInput {
  return {
    id:
      typeof crypto !== "undefined" && "randomUUID" in crypto
        ? crypto.randomUUID()
        : `template-task-${Date.now()}-${index}`,
    title: "",
    description: "",
    estimated_hours: null,
    priority: "medium",
    sort_order: index,
  };
}

function normalizeTasks(
  tasks: ProjectTemplateTaskInput[]
): ProjectTemplateTaskInput[] {
  return tasks.map((task, index) => ({
    ...task,
    sort_order: index,
  }));
}

interface SortableTaskItemProps {
  index: number;
  task: ProjectTemplateTaskInput;
  onUpdate: (
    taskId: string,
    field: keyof ProjectTemplateTaskInput,
    value: string | number | null
  ) => void;
  onRemove: (taskId: string) => void;
}

function SortableTaskItem({
  index,
  task,
  onUpdate,
  onRemove,
}: SortableTaskItemProps) {
  const { attributes, listeners, setNodeRef, transform, transition } =
    useSortable({ id: task.id });

  return (
    <div
      ref={setNodeRef}
      style={{
        transform: CSS.Transform.toString(transform),
        transition,
      }}
      className="rounded-lg border p-4"
    >
      <div className="mb-3 flex items-start justify-between gap-3">
        <div className="flex items-center gap-2">
          <button
            type="button"
            className="cursor-grab rounded-md border p-2 text-muted-foreground"
            aria-label={`Réordonner la tâche ${index + 1}`}
            {...attributes}
            {...listeners}
          >
            <GripVertical className="h-4 w-4" />
          </button>
          <p className="text-sm font-medium">Tâche {index + 1}</p>
        </div>
        <Button
          type="button"
          variant="ghost"
          size="icon"
          aria-label={`Supprimer la tâche ${index + 1}`}
          onClick={() => onRemove(task.id)}
        >
          <Trash2 className="h-4 w-4" />
        </Button>
      </div>

      <div className="space-y-3">
        <div className="space-y-2">
          <Label htmlFor={`task-title-${task.id}`}>Titre</Label>
          <Input
            id={`task-title-${task.id}`}
            value={task.title}
            onChange={(event) => onUpdate(task.id, "title", event.target.value)}
          />
        </div>

        <div className="space-y-2">
          <Label htmlFor={`task-description-${task.id}`}>Description</Label>
          <Textarea
            id={`task-description-${task.id}`}
            rows={2}
            value={task.description}
            onChange={(event) =>
              onUpdate(task.id, "description", event.target.value)
            }
          />
        </div>

        <div className="grid gap-3 md:grid-cols-2">
          <div className="space-y-2">
            <Label htmlFor={`task-hours-${task.id}`}>Heures estimées</Label>
            <Input
              id={`task-hours-${task.id}`}
              type="number"
              step="0.25"
              value={task.estimated_hours ?? ""}
              onChange={(event) =>
                onUpdate(
                  task.id,
                  "estimated_hours",
                  event.target.value === ""
                    ? null
                    : Number(event.target.value)
                )
              }
            />
          </div>

          <div className="space-y-2">
            <Label>Priorité</Label>
            <Select
              value={task.priority}
              onValueChange={(value) => onUpdate(task.id, "priority", value)}
            >
              <SelectTrigger>
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="low">Low</SelectItem>
                <SelectItem value="medium">Medium</SelectItem>
                <SelectItem value="high">High</SelectItem>
                <SelectItem value="urgent">Urgent</SelectItem>
              </SelectContent>
            </Select>
          </div>
        </div>
      </div>
    </div>
  );
}

export function ProjectTemplateTaskBuilder({
  value,
  onChange,
}: ProjectTemplateTaskBuilderProps) {
  const sensors = useSensors(useSensor(PointerSensor));

  const handleAddTask = () => {
    onChange(normalizeTasks([...value, createTask(value.length)]));
  };

  const handleRemoveTask = (taskId: string) => {
    onChange(normalizeTasks(value.filter((task) => task.id !== taskId)));
  };

  const handleUpdateTask = (
    taskId: string,
    field: keyof ProjectTemplateTaskInput,
    fieldValue: string | number | null
  ) => {
    onChange(
      normalizeTasks(
        value.map((task) =>
          task.id === taskId ? { ...task, [field]: fieldValue } : task
        )
      )
    );
  };

  const handleDragEnd = (event: DragEndEvent) => {
    const { active, over } = event;

    if (!over || active.id === over.id) {
      return;
    }

    const oldIndex = value.findIndex((task) => task.id === active.id);
    const newIndex = value.findIndex((task) => task.id === over.id);

    onChange(normalizeTasks(arrayMove(value, oldIndex, newIndex)));
  };

  return (
    <div className="space-y-4">
      <div className="flex items-center justify-between">
        <h3 className="text-lg font-semibold">Tâches du template</h3>
        <Button type="button" variant="outline" onClick={handleAddTask}>
          Ajouter une tâche
        </Button>
      </div>

      {value.length === 0 ? (
        <div className="rounded-lg border border-dashed p-4 text-sm text-muted-foreground">
          Aucune tâche pour ce template.
        </div>
      ) : (
        <DndContext
          sensors={sensors}
          collisionDetection={closestCenter}
          onDragEnd={handleDragEnd}
        >
          <SortableContext
            items={value.map((task) => task.id)}
            strategy={verticalListSortingStrategy}
          >
            <div className="space-y-3">
              {value.map((task, index) => (
                <SortableTaskItem
                  key={task.id}
                  index={index}
                  task={task}
                  onUpdate={handleUpdateTask}
                  onRemove={handleRemoveTask}
                />
              ))}
            </div>
          </SortableContext>
        </DndContext>
      )}
    </div>
  );
}

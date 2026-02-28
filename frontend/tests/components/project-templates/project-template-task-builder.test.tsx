import { fireEvent, render, screen } from "@testing-library/react";
import { describe, expect, it, vi } from "vitest";
import {
  ProjectTemplateTaskBuilder,
  type ProjectTemplateTaskInput,
} from "@/components/project-templates/project-template-task-builder";

const initialTasks: ProjectTemplateTaskInput[] = [
  {
    id: "task-1",
    title: "Kickoff",
    description: "",
    estimated_hours: null,
    priority: "medium",
    sort_order: 0,
  },
];

describe("ProjectTemplateTaskBuilder", () => {
  it("adds a new task", () => {
    const onChange = vi.fn();

    render(
      <ProjectTemplateTaskBuilder value={initialTasks} onChange={onChange} />
    );

    fireEvent.click(screen.getByRole("button", { name: /ajouter une tâche/i }));

    expect(onChange).toHaveBeenCalledWith(
      expect.arrayContaining([
        expect.objectContaining({ title: "Kickoff", sort_order: 0 }),
        expect.objectContaining({ title: "", sort_order: 1 }),
      ])
    );
  });

  it("removes a task", () => {
    const onChange = vi.fn();

    render(
      <ProjectTemplateTaskBuilder value={initialTasks} onChange={onChange} />
    );

    fireEvent.click(
      screen.getByRole("button", { name: /supprimer la tâche 1/i })
    );

    expect(onChange).toHaveBeenCalledWith([]);
  });
});

import { render, screen } from "@testing-library/react";
import { describe, expect, it } from "vitest";
import { ProjectOverview } from "@/components/projects/project-overview";

describe("ProjectOverview", () => {
  it("displays progress, time and budget information", () => {
    render(
      <ProjectOverview
        project={{
          id: "p1",
          name: "Platform",
          status: "in_progress",
          progress_percentage: 72,
          total_time_spent: 420,
          budget_consumed: 3500,
          total_tasks: 12,
          completed_tasks: 9,
          billing_type: "hourly",
        }}
      />
    );

    expect(screen.getByText("72% complete")).toBeInTheDocument();
    expect(screen.getByText("7h 0m tracked")).toBeInTheDocument();
    expect(screen.getByText("3,500.00 EUR used")).toBeInTheDocument();
    expect(screen.getByText("9 / 12 tasks completed")).toBeInTheDocument();
  });
});

import { render, screen } from "@testing-library/react";
import { describe, expect, it } from "vitest";
import { TimeTrackedTodayWidget } from "@/components/dashboard/time-tracked-today-widget";

describe("TimeTrackedTodayWidget", () => {
  it("renders nothing when no time was tracked today", () => {
    const { container } = render(
      <TimeTrackedTodayWidget minutesToday={0} entriesCount={0} />
    );

    expect(container).toBeEmptyDOMElement();
  });

  it("renders tracked time summary when minutes are available", () => {
    render(<TimeTrackedTodayWidget minutesToday={135} entriesCount={3} />);

    expect(screen.getByText("Temps suivi aujourd'hui")).toBeInTheDocument();
    expect(screen.getByText("2 h 15")).toBeInTheDocument();
    expect(screen.getByText("3 entrées")).toBeInTheDocument();
  });
});

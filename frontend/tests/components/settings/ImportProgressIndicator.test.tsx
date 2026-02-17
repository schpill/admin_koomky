import { render, screen } from "@testing-library/react";
import { describe, expect, it } from "vitest";
import { ImportProgressIndicator } from "@/components/settings/import-progress-indicator";

describe("ImportProgressIndicator", () => {
  it("renders active step and progress percentage", () => {
    render(<ImportProgressIndicator stage="creating" />);

    expect(screen.getByText("Creating records")).toBeInTheDocument();
    expect(screen.getByText("80%")).toBeInTheDocument();
  });
});

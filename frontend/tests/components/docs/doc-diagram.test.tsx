import { render, screen, waitFor } from "@testing-library/react";
import { beforeEach, describe, expect, it, vi } from "vitest";
import { DocDiagram } from "@/components/docs/doc-diagram";

const renderMock = vi.fn().mockResolvedValue({
  svg: "<svg><text>Diagram</text></svg>",
  bindFunctions: vi.fn(),
});

vi.mock("mermaid", () => ({
  default: {
    initialize: vi.fn(),
    render: (...args: unknown[]) => renderMock(...args),
  },
}));

describe("DocDiagram", () => {
  beforeEach(() => {
    renderMock.mockClear();
    vi.stubGlobal(
      "fetch",
      vi.fn().mockResolvedValue({
        ok: true,
        text: async () => "graph TD; A-->B;",
      })
    );
  });

  it("fetches the mermaid source and renders the generated svg", async () => {
    render(
      <DocDiagram src="/docs/diagrams/leads-conversion-flow.mmd" title="Flow" />
    );

    await waitFor(() => {
      expect(renderMock).toHaveBeenCalledTimes(1);
    });

    expect(screen.getByText("Flow")).toBeInTheDocument();
    expect(screen.getByTestId("doc-diagram-svg").innerHTML).toContain("Diagram");
  });
});

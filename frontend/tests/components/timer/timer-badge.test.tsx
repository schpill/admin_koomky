import { render, screen } from "@testing-library/react";
import { beforeEach, describe, expect, it, vi } from "vitest";
import { TimerBadge } from "@/components/timer/timer-badge";

const timerStoreMock = vi.fn();

vi.mock("@/lib/stores/timer", () => ({
  useTimerStore: () => timerStoreMock(),
}));

describe("TimerBadge", () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it("renders nothing when no timer is running", () => {
    timerStoreMock.mockReturnValue({
      isRunning: false,
      elapsedSeconds: 0,
      tick: vi.fn(),
      fetchActive: vi.fn(),
    });

    const { container } = render(<TimerBadge />);

    expect(container).toBeEmptyDOMElement();
  });

  it("renders the elapsed time when a timer is running", async () => {
    timerStoreMock.mockReturnValue({
      isRunning: true,
      elapsedSeconds: 3661,
      tick: vi.fn(),
      fetchActive: vi.fn(),
    });

    render(<TimerBadge />);

    expect(await screen.findByText("01:01:01")).toBeInTheDocument();
  });
});

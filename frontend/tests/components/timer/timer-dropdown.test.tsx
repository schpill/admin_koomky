import { fireEvent, render, screen, waitFor } from "@testing-library/react";
import { beforeEach, describe, expect, it, vi } from "vitest";
import { TimerDropdown } from "@/components/timer/timer-dropdown";

const timerStoreMock = vi.fn();
const toastSuccess = vi.fn();
const toastError = vi.fn();

vi.mock("@/lib/stores/timer", () => ({
  useTimerStore: () => timerStoreMock(),
}));

vi.mock("sonner", () => ({
  toast: {
    success: (...args: unknown[]) => toastSuccess(...args),
    error: (...args: unknown[]) => toastError(...args),
  },
}));

describe("TimerDropdown", () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it("renders an empty state when no timer is active", () => {
    timerStoreMock.mockReturnValue({
      activeEntry: null,
      elapsedSeconds: 0,
      isLoading: false,
      stopTimer: vi.fn(),
      cancelTimer: vi.fn(),
    });

    render(<TimerDropdown />);

    expect(screen.getByText("Aucun timer actif")).toBeInTheDocument();
  });

  it("stops the active timer", async () => {
    const stopTimer = vi.fn().mockResolvedValue(undefined);

    timerStoreMock.mockReturnValue({
      activeEntry: {
        id: "entry-1",
        task_name: "Write tests",
        project_name: "Phase 13",
        description: "Focus",
      },
      elapsedSeconds: 90,
      isLoading: false,
      stopTimer,
      cancelTimer: vi.fn(),
    });

    render(<TimerDropdown />);
    fireEvent.click(screen.getByRole("button", { name: /arrêter/i }));

    await waitFor(() => {
      expect(stopTimer).toHaveBeenCalledOnce();
      expect(toastSuccess).toHaveBeenCalled();
    });
  });
});

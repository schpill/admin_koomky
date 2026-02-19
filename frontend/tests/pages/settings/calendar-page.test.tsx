import { beforeEach, describe, expect, it, vi } from "vitest";
import { fireEvent, render, screen, waitFor } from "@testing-library/react";
import { I18nProvider } from "@/components/providers/i18n-provider";

const toastSuccess = vi.fn();
const toastError = vi.fn();
const useCalendarStoreMock = vi.fn();

vi.mock("sonner", () => ({
  toast: {
    success: (...args: unknown[]) => toastSuccess(...args),
    error: (...args: unknown[]) => toastError(...args),
  },
}));

vi.mock("@/lib/stores/calendar", () => ({
  useCalendarStore: () => useCalendarStoreMock(),
}));

import CalendarSettingsPage from "@/app/(dashboard)/settings/calendar/page";

describe("CalendarSettingsPage", () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it("loads settings and validates connection creation", async () => {
    const fetchConnections = vi.fn().mockResolvedValue(undefined);
    const fetchAutoEventRules = vi.fn().mockResolvedValue(undefined);
    const createConnection = vi.fn().mockResolvedValue({ id: "conn_1" });
    const updateAutoEventRules = vi.fn().mockResolvedValue({
      project_deadlines: true,
      task_due_dates: false,
      invoice_reminders: true,
    });

    useCalendarStoreMock.mockReturnValue({
      connections: [],
      isLoading: false,
      fetchConnections,
      createConnection,
      updateConnection: vi.fn(),
      deleteConnection: vi.fn(),
      autoEventRules: {
        project_deadlines: true,
        task_due_dates: true,
        invoice_reminders: true,
      },
      fetchAutoEventRules,
      updateAutoEventRules,
    });

    render(
      <I18nProvider initialLocale="en">
        <CalendarSettingsPage />
      </I18nProvider>
    );

    await waitFor(() => {
      expect(fetchConnections).toHaveBeenCalledTimes(1);
      expect(fetchAutoEventRules).toHaveBeenCalledTimes(1);
    });

    fireEvent.click(screen.getByRole("button", { name: "Save connection" }));

    expect(toastError).toHaveBeenCalledWith("Connection name is required");

    fireEvent.change(screen.getByLabelText("Connection name"), {
      target: { value: "Personal CalDAV" },
    });

    fireEvent.change(screen.getByLabelText("Provider"), {
      target: { value: "caldav" },
    });

    fireEvent.click(screen.getByRole("button", { name: "Save connection" }));

    await waitFor(() => {
      expect(createConnection).toHaveBeenCalledWith(
        expect.objectContaining({
          provider: "caldav",
          name: "Personal CalDAV",
          sync_enabled: true,
        })
      );
      expect(toastSuccess).toHaveBeenCalledWith("Calendar connection created");
      expect(fetchConnections).toHaveBeenCalledTimes(2);
    });

    fireEvent.click(
      screen.getByRole("button", { name: "Save auto-event rules" })
    );

    await waitFor(() => {
      expect(updateAutoEventRules).toHaveBeenCalledTimes(1);
      expect(toastSuccess).toHaveBeenCalledWith("Auto-event rules saved");
    });
  });

  it("handles connection actions", async () => {
    const fetchConnections = vi.fn().mockResolvedValue(undefined);
    const updateConnection = vi.fn().mockResolvedValue({});
    const deleteConnection = vi.fn().mockResolvedValue({});

    useCalendarStoreMock.mockReturnValue({
      connections: [
        {
          id: "conn_1",
          name: "Google Work",
          provider: "google",
          sync_enabled: true,
        },
      ],
      isLoading: false,
      fetchConnections,
      createConnection: vi.fn(),
      updateConnection,
      deleteConnection,
      autoEventRules: {
        project_deadlines: true,
        task_due_dates: true,
        invoice_reminders: true,
      },
      fetchAutoEventRules: vi.fn().mockResolvedValue(undefined),
      updateAutoEventRules: vi.fn().mockResolvedValue(undefined),
    });

    render(
      <I18nProvider initialLocale="en">
        <CalendarSettingsPage />
      </I18nProvider>
    );

    fireEvent.click(screen.getByRole("button", { name: "Disable" }));

    await waitFor(() => {
      expect(updateConnection).toHaveBeenCalledWith("conn_1", {
        sync_enabled: false,
      });
    });

    fireEvent.click(screen.getByRole("button", { name: "Delete" }));

    await waitFor(() => {
      expect(deleteConnection).toHaveBeenCalledWith("conn_1");
      expect(fetchConnections).toHaveBeenCalledTimes(3);
    });
  });

  it("shows load error when auto-event rules fetch fails", async () => {
    useCalendarStoreMock.mockReturnValue({
      connections: [],
      isLoading: false,
      fetchConnections: vi.fn().mockResolvedValue(undefined),
      createConnection: vi.fn(),
      updateConnection: vi.fn(),
      deleteConnection: vi.fn(),
      autoEventRules: {
        project_deadlines: true,
        task_due_dates: true,
        invoice_reminders: true,
      },
      fetchAutoEventRules: vi.fn().mockRejectedValue(new Error("load failed")),
      updateAutoEventRules: vi.fn().mockResolvedValue(undefined),
    });

    render(
      <I18nProvider initialLocale="en">
        <CalendarSettingsPage />
      </I18nProvider>
    );

    await waitFor(() => {
      expect(toastError).toHaveBeenCalledWith(
        "Unable to load calendar settings"
      );
    });
  });
});

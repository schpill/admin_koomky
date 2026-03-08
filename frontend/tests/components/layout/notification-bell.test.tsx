import type { ReactNode } from "react";
import { render, screen } from "@testing-library/react";
import { beforeEach, describe, expect, it, vi } from "vitest";
import { I18nProvider } from "@/components/providers/i18n-provider";
import { NotificationBell } from "@/components/layout/notification-bell";
import { useNotificationStore } from "@/lib/stores/notifications";

vi.mock("@/components/ui/dropdown-menu", () => ({
  DropdownMenu: ({ children }: { children: ReactNode }) => (
    <div>{children}</div>
  ),
  DropdownMenuTrigger: ({ children }: { children: ReactNode }) => (
    <div>{children}</div>
  ),
  DropdownMenuContent: ({ children }: { children: ReactNode }) => (
    <div>{children}</div>
  ),
  DropdownMenuLabel: ({ children }: { children: ReactNode }) => (
    <div>{children}</div>
  ),
  DropdownMenuSeparator: () => <hr />,
  DropdownMenuItem: ({
    children,
    onClick,
  }: {
    children: ReactNode;
    onClick?: () => void;
  }) => <button onClick={onClick}>{children}</button>,
}));

describe("NotificationBell", () => {
  beforeEach(() => {
    useNotificationStore.setState({
      notifications: [],
    });
  });

  it("renders translated empty state in French", async () => {
    render(
      <I18nProvider initialLocale="fr">
        <NotificationBell />
      </I18nProvider>
    );

    expect(await screen.findByText("Tout marquer lu")).toBeInTheDocument();
    expect(
      screen.getByText("Aucune notification pour le moment.")
    ).toBeInTheDocument();
  });

  it("renders translated unread content in English", async () => {
    useNotificationStore.setState({
      notifications: [
        {
          id: "notif_1",
          title: "Payment received",
          body: "Invoice INV-001 was paid.",
          created_at: "2026-03-08T10:00:00.000Z",
          read_at: null,
        },
      ],
    });

    render(
      <I18nProvider initialLocale="en">
        <NotificationBell />
      </I18nProvider>
    );

    expect(await screen.findByText("Mark all read")).toBeInTheDocument();
    expect(screen.getByText("Payment received")).toBeInTheDocument();
    expect(screen.getByText("Unread")).toBeInTheDocument();
  });
});

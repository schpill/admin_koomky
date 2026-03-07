import { fireEvent, render, screen, waitFor } from "@testing-library/react";
import { beforeEach, describe, expect, it, vi } from "vitest";
import { ClientContactList } from "@/components/clients/client-contact-list";

vi.mock("@/lib/api", () => ({
  apiClient: {
    get: vi.fn(),
    post: vi.fn(),
    put: vi.fn(),
    delete: vi.fn(),
  },
}));

vi.mock("@/components/providers/i18n-provider", () => ({
  useI18n: () => ({
    t: (key: string) => key,
  }),
}));

import { apiClient } from "@/lib/api";

describe("ClientContactList", () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it("renders the email score badge for each contact", async () => {
    (apiClient.get as any).mockResolvedValueOnce({
      data: [
        {
          id: "contact_1",
          first_name: "Jane",
          last_name: "Doe",
          email: "jane@example.test",
          phone: null,
          position: "CEO",
          is_primary: true,
          email_score: 72,
        },
      ],
    });

    render(<ClientContactList clientId="client_1" />);

    await waitFor(() => {
      expect(screen.getByText("72")).toBeInTheDocument();
    });
  });

  it("submits and renders the contact timezone", async () => {
    (apiClient.get as any)
      .mockResolvedValueOnce({ data: [] })
      .mockResolvedValueOnce({
        data: [
          {
            id: "contact_2",
            first_name: "Jane",
            last_name: "Doe",
            email: "jane@example.test",
            phone: null,
            position: "CEO",
            timezone: "Europe/Paris",
            is_primary: false,
            email_score: 80,
          },
        ],
      });
    (apiClient.post as any).mockResolvedValueOnce({ data: {} });

    render(<ClientContactList clientId="client_1" />);

    fireEvent.click(await screen.findByText("clients.contacts.addContact"));
    fireEvent.change(screen.getByLabelText("clients.contacts.firstName"), {
      target: { value: "Jane" },
    });
    fireEvent.change(screen.getByLabelText("clients.contacts.timezone"), {
      target: { value: "Europe/Paris" },
    });
    fireEvent.click(screen.getByText("common.save"));

    await waitFor(() => {
      expect(apiClient.post).toHaveBeenCalledWith("/clients/client_1/contacts", {
        first_name: "Jane",
        last_name: "",
        email: "",
        phone: "",
        position: "",
        timezone: "Europe/Paris",
        is_primary: false,
      });
    });

    await waitFor(() => {
      expect(screen.getByText(/Europe\/Paris/)).toBeInTheDocument();
    });
  });
});

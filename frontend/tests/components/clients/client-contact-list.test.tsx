import { render, screen, waitFor } from "@testing-library/react";
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
});

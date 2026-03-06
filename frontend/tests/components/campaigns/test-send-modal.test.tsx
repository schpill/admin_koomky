import { describe, expect, it, vi } from "vitest";
import { fireEvent, render, screen } from "@testing-library/react";
import { TestSendModal } from "@/components/campaigns/test-send-modal";
import { I18nProvider } from "@/components/providers/i18n-provider";

function renderWithI18n(component: React.ReactNode) {
  return render(<I18nProvider initialLocale="fr">{component}</I18nProvider>);
}

describe("TestSendModal", () => {
  it("collects up to 5 emails and sends array payload", async () => {
    const onSend = vi.fn().mockResolvedValue(undefined);

    renderWithI18n(<TestSendModal type="email" onSend={onSend} />);

    fireEvent.click(screen.getByRole("button", { name: "Send test" }));

    const input = screen.getByLabelText("Emails (1-5)");

    fireEvent.change(input, { target: { value: "a@example.test" } });
    fireEvent.click(screen.getByRole("button", { name: "Ajouter" }));
    fireEvent.change(input, { target: { value: "b@example.test" } });
    fireEvent.click(screen.getByRole("button", { name: "Ajouter" }));

    fireEvent.click(screen.getByRole("button", { name: "Send" }));

    expect(onSend).toHaveBeenCalledWith({
      emails: ["a@example.test", "b@example.test"],
    });
  });

  it("validates malformed email", () => {
    const onSend = vi.fn().mockResolvedValue(undefined);

    renderWithI18n(<TestSendModal type="email" onSend={onSend} />);
    fireEvent.click(screen.getByRole("button", { name: "Send test" }));

    const input = screen.getByLabelText("Emails (1-5)");
    fireEvent.change(input, { target: { value: "not-an-email" } });
    fireEvent.click(screen.getByRole("button", { name: "Ajouter" }));

    expect(screen.getByText("Format email invalide.")).toBeInTheDocument();
  });
});

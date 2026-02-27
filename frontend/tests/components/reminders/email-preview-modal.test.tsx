import { render, screen } from "@testing-library/react";
import { describe, expect, it } from "vitest";
import { EmailPreviewModal } from "@/components/reminders/email-preview-modal";
import { I18nProvider } from "@/components/providers/i18n-provider";

describe("EmailPreviewModal", () => {
  it("interpolates variables", () => {
    render(
      <I18nProvider initialLocale="fr">
        <EmailPreviewModal
          open
          onOpenChange={() => {}}
          step={{
            step_number: 1,
            delay_days: 3,
            subject: "x",
            body: "Bonjour {{client_name}}",
          }}
        />
      </I18nProvider>
    );

    expect(screen.getByText(/Jean Dupont/)).toBeInTheDocument();
  });
});

import { fireEvent, render, screen } from "@testing-library/react";
import { describe, expect, it } from "vitest";
import { I18nProvider } from "@/components/providers/i18n-provider";
import { DocScreenshot } from "@/components/docs/doc-screenshot";

describe("DocScreenshot", () => {
  it("renders the screenshot and opens a fullscreen dialog", () => {
    render(
      <I18nProvider initialLocale="en">
        <DocScreenshot
          src="/docs/screenshots/dashboard/overview.png"
          alt="Dashboard overview"
          caption="Main dashboard widgets"
        />
      </I18nProvider>
    );

    expect(screen.getByAltText("Dashboard overview")).toBeInTheDocument();
    expect(screen.getByText("Main dashboard widgets")).toBeInTheDocument();

    fireEvent.click(
      screen.getByRole("button", { name: /dashboard overview/i })
    );

    expect(screen.getAllByAltText("Dashboard overview").length).toBeGreaterThan(
      1
    );
  });
});

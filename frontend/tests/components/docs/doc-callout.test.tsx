import { render, screen } from "@testing-library/react";
import { describe, expect, it } from "vitest";
import { I18nProvider } from "@/components/providers/i18n-provider";
import { DocCallout } from "@/components/docs/doc-callout";

describe("DocCallout", () => {
  it("renders content for each supported variant", () => {
    render(
      <I18nProvider initialLocale="en">
        <div>
          <DocCallout type="info">Info block</DocCallout>
          <DocCallout type="tip">Tip block</DocCallout>
          <DocCallout type="warning">Warning block</DocCallout>
          <DocCallout type="danger">Danger block</DocCallout>
        </div>
      </I18nProvider>
    );

    expect(screen.getByText("Info block")).toBeInTheDocument();
    expect(screen.getByText("Tip block")).toBeInTheDocument();
    expect(screen.getByText("Warning block")).toBeInTheDocument();
    expect(screen.getByText("Danger block")).toBeInTheDocument();
  });
});

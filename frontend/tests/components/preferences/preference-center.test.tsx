import { act, fireEvent, render, screen } from "@testing-library/react";
import { describe, expect, it, vi } from "vitest";
import { PreferenceCenterForm } from "@/components/portal/preference-center-form";

describe("PreferenceCenterForm", () => {
  it("submits updated preferences", async () => {
    const onSubmit = vi.fn();

    render(
      <PreferenceCenterForm
        initialPreferences={[
          { category: "newsletter", subscribed: true },
          { category: "promotional", subscribed: true },
          { category: "transactional", subscribed: true },
        ]}
        onSubmit={onSubmit}
      />
    );

    await act(async () => {
      fireEvent.click(
        screen.getByRole("button", { name: /unsubscribe from all marketing/i })
      );
    });

    await act(async () => {
      fireEvent.click(
        screen.getByRole("button", { name: /save preferences/i })
      );
    });

    expect(onSubmit).toHaveBeenCalledWith([
      { category: "newsletter", subscribed: false },
      { category: "promotional", subscribed: false },
      { category: "transactional", subscribed: true },
    ]);
  });
});

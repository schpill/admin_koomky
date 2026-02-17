import { render, screen } from "@testing-library/react";
import { describe, it, expect } from "vitest";
import { SmsPreview } from "@/components/campaigns/sms-preview";

describe("SmsPreview", () => {
  it("renders personalized message in phone mockup", () => {
    render(
      <SmsPreview
        content="Hi {{first_name}}, your company is {{company}}"
        recipient={{
          first_name: "Alice",
          last_name: "Doe",
          email: "alice@example.com",
          company: "Acme",
        }}
      />
    );

    expect(screen.getByText("Hi Alice, your company is Acme")).toBeInTheDocument();
  });
});

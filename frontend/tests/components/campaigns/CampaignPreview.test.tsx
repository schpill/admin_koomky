import { render, screen, fireEvent } from "@testing-library/react";
import { describe, it, expect } from "vitest";
import { CampaignPreview } from "@/components/campaigns/campaign-preview";

const recipients = [
  {
    id: "r1",
    first_name: "Alice",
    last_name: "Doe",
    email: "alice@example.com",
    company: "Acme",
  },
  {
    id: "r2",
    first_name: "Bob",
    last_name: "Moe",
    email: "bob@example.com",
    company: "Globex",
  },
];

describe("CampaignPreview", () => {
  it("renders personalized content", () => {
    render(
      <CampaignPreview
        subject="Welcome {{first_name}}"
        content="Hello {{first_name}} from {{company}}"
        recipients={recipients}
      />
    );

    expect(screen.getByText("Welcome Alice")).toBeInTheDocument();
    expect(screen.getByText("Hello Alice from Acme")).toBeInTheDocument();
  });

  it("switches preview recipient", () => {
    render(
      <CampaignPreview
        subject="Welcome {{first_name}}"
        content="Hello {{first_name}} from {{company}}"
        recipients={recipients}
      />
    );

    fireEvent.change(screen.getByLabelText("Preview recipient"), {
      target: { value: "r2" },
    });

    expect(screen.getByText("Welcome Bob")).toBeInTheDocument();
    expect(screen.getByText("Hello Bob from Globex")).toBeInTheDocument();
  });
});

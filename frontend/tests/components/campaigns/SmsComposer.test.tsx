import { render, screen, fireEvent } from "@testing-library/react";
import { describe, it, expect, vi } from "vitest";
import { SmsComposer } from "@/components/campaigns/sms-composer";

function repeat(char: string, length: number): string {
  return Array.from({ length })
    .map(() => char)
    .join("");
}

describe("SmsComposer", () => {
  it("shows character and segment counters", () => {
    render(<SmsComposer value={repeat("a", 160)} onChange={vi.fn()} />);

    expect(screen.getByText("160 / 160")).toBeInTheDocument();
    expect(screen.getByText("1 segment")).toBeInTheDocument();
  });

  it("increments segment count after 160 chars", () => {
    render(<SmsComposer value={repeat("a", 161)} onChange={vi.fn()} />);

    expect(screen.getByText("2 segments")).toBeInTheDocument();
  });

  it("inserts personalization variable", () => {
    const onChange = vi.fn();

    render(<SmsComposer value="Hello" onChange={onChange} />);

    fireEvent.click(
      screen.getByRole("button", { name: "Insert {{first_name}}" })
    );

    expect(onChange).toHaveBeenCalled();
  });

  it("shows warning when message is long", () => {
    render(<SmsComposer value={repeat("a", 321)} onChange={vi.fn()} />);

    expect(screen.getByText(/This message will be split/i)).toBeInTheDocument();
  });
});

import { render, screen, fireEvent } from "@testing-library/react";
import { describe, it, expect, vi } from "vitest";
import { EmailEditor } from "@/components/campaigns/email-editor";

describe("EmailEditor", () => {
  it("renders editor and counters", () => {
    render(<EmailEditor value="Hello world" onChange={vi.fn()} />);

    expect(screen.getByLabelText("Email content")).toBeInTheDocument();
    expect(screen.getByText(/11 characters/i)).toBeInTheDocument();
  });

  it("applies formatting and variable insertion", () => {
    const onChange = vi.fn();

    render(<EmailEditor value="Hello" onChange={onChange} />);

    fireEvent.click(screen.getByRole("button", { name: "Bold" }));
    fireEvent.click(
      screen.getByRole("button", { name: "Insert {{first_name}}" })
    );

    expect(onChange).toHaveBeenCalled();
  });

  it("toggles html source mode", () => {
    render(<EmailEditor value="<p>Hello</p>" onChange={vi.fn()} />);

    fireEvent.click(screen.getByRole("button", { name: /HTML source/i }));

    expect(screen.getByText(/Source mode enabled/i)).toBeInTheDocument();
  });
});

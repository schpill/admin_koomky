import { describe, expect, it } from "vitest";
import { render, screen } from "@testing-library/react";
import { DocumentTypeBadge } from "@/components/documents/document-type-badge";

describe("DocumentTypeBadge", () => {
  it("renders correctly for PDF", () => {
    render(<DocumentTypeBadge type="pdf" />);
    expect(screen.getByText("PDF")).toBeInTheDocument();
  });

  it("renders correctly for image", () => {
    render(<DocumentTypeBadge type="image" />);
    expect(screen.getByText("Image")).toBeInTheDocument();
  });

  it("shows script language for script type", () => {
    render(<DocumentTypeBadge type="script" scriptLanguage="python" />);
    expect(screen.getByText("python")).toBeInTheDocument();
  });

  it("shows default label for script type without language", () => {
    render(<DocumentTypeBadge type="script" />);
    expect(screen.getByText("Script")).toBeInTheDocument();
  });

  it("hides label when showLabel is false", () => {
    render(<DocumentTypeBadge type="pdf" showLabel={false} />);
    expect(screen.queryByText("PDF")).not.toBeInTheDocument();
  });
});

import { describe, it, expect, vi } from "vitest";
import { fireEvent, render, screen } from "@testing-library/react";
import { useState } from "react";
import { ReceiptUpload } from "@/components/expenses/receipt-upload";

if (!global.URL.createObjectURL) {
  global.URL.createObjectURL = vi.fn(() => "blob:receipt") as any;
}

function ReceiptUploadHarness() {
  const [file, setFile] = useState<File | null>(null);

  return <ReceiptUpload file={file} onChange={setFile} />;
}

describe("ReceiptUpload", () => {
  it("accepts drag and drop uploads", async () => {
    render(<ReceiptUploadHarness />);

    const dropzoneText = screen.getByText(/drag and drop an image or pdf/i);
    const dropzone = dropzoneText.closest(".border-dashed") as HTMLDivElement;
    const file = new File(["hello"], "receipt.jpg", { type: "image/jpeg" });

    fireEvent.dragOver(dropzone);
    fireEvent.dragLeave(dropzone);
    fireEvent.drop(dropzone, {
      dataTransfer: {
        files: [file],
      },
    });

    expect(await screen.findByAltText("Receipt preview")).toBeInTheDocument();
  });

  it("accepts image upload and supports removal", async () => {
    render(<ReceiptUploadHarness />);

    const input = document.querySelector(
      'input[type="file"][accept="image/*,application/pdf"]'
    ) as HTMLInputElement;

    const file = new File(["hello"], "receipt.jpg", { type: "image/jpeg" });

    fireEvent.change(input, { target: { files: [file] } });

    expect(await screen.findByAltText("Receipt preview")).toBeInTheDocument();

    fireEvent.click(screen.getByRole("button", { name: /remove/i }));

    expect(screen.queryByAltText("Receipt preview")).not.toBeInTheDocument();
  });

  it("renders generic attachment fallback for non-image/non-pdf files", async () => {
    render(<ReceiptUploadHarness />);

    const input = document.querySelector(
      'input[type="file"][accept="image/*,application/pdf"]'
    ) as HTMLInputElement;

    const file = new File(["hello"], "notes.txt", { type: "text/plain" });

    fireEvent.change(input, { target: { files: [file] } });

    expect(await screen.findByText("File attached")).toBeInTheDocument();
  });

  it("renders pdf preview for pdf files", async () => {
    render(<ReceiptUploadHarness />);

    const input = document.querySelector(
      'input[type="file"][accept="image/*,application/pdf"]'
    ) as HTMLInputElement;

    const file = new File(["%PDF"], "receipt.pdf", {
      type: "application/pdf",
    });

    fireEvent.change(input, { target: { files: [file] } });

    expect(await screen.findByTitle("Receipt PDF preview")).toBeInTheDocument();
  });

  it("ignores empty file selections", () => {
    const onChange = vi.fn();
    render(<ReceiptUpload file={null} onChange={onChange} />);

    const input = document.querySelector(
      'input[type="file"][accept="image/*,application/pdf"]'
    ) as HTMLInputElement;

    fireEvent.change(input, { target: { files: [] } });

    expect(onChange).not.toHaveBeenCalled();
  });
});

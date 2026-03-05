import { fireEvent, render, screen } from "@testing-library/react";
import { describe, expect, it, vi } from "vitest";
import { StepUpload } from "@/components/prospects/import-wizard/step-upload";

describe("StepUpload", () => {
  it("accepts csv file and triggers analyze", async () => {
    const onAnalyze = vi.fn().mockResolvedValue(undefined);
    render(<StepUpload isUploading={false} onAnalyze={onAnalyze} />);

    const input = screen.getByTestId("import-file-input") as HTMLInputElement;
    const file = new File(["a"], "prospects.csv", { type: "text/csv" });
    fireEvent.change(input, { target: { files: [file] } });

    fireEvent.click(
      screen.getByRole("button", { name: /Analyser le fichier/i })
    );
    expect(onAnalyze).toHaveBeenCalled();
  });
});

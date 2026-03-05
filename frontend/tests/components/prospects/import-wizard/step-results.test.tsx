import { render, screen } from "@testing-library/react";
import { describe, expect, it, vi } from "vitest";
import { StepResults } from "@/components/prospects/import-wizard/step-results";

describe("StepResults", () => {
  it("renders summary and errors", () => {
    render(
      <StepResults
        session={{
          id: "imp_1",
          status: "completed",
          total_rows: 10,
          processed_rows: 10,
          success_rows: 8,
          error_rows: 2,
        }}
        progress={100}
        errors={[
          { id: "e1", row_number: 3, raw_data: {}, error_message: "Email invalide" },
        ]}
        isProcessing={false}
        onExportErrors={vi.fn().mockResolvedValue(undefined)}
      />
    );

    expect(screen.getByText(/Importés: 8/i)).toBeInTheDocument();
    expect(screen.getByText(/Ligne 3/i)).toBeInTheDocument();
  });
});

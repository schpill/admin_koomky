import { render, screen } from "@testing-library/react";
import { describe, expect, it } from "vitest";
import { ImportErrorReport } from "@/components/settings/import-error-report";

describe("ImportErrorReport", () => {
  it("displays row, field and message entries", () => {
    render(
      <ImportErrorReport
        errors={[
          { row: 3, field: "client_reference", message: "Client not found" },
        ]}
      />
    );

    expect(screen.getByText("Row 3")).toBeInTheDocument();
    expect(screen.getByText("client_reference")).toBeInTheDocument();
    expect(screen.getByText("Client not found")).toBeInTheDocument();
  });
});

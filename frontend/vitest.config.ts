import { defineConfig } from "vitest/config";
import react from "@vitejs/plugin-react";
import path from "path";

export default defineConfig({
  plugins: [react()],
  test: {
    environment: "jsdom",
    globals: true,
    setupFiles: "./tests/setup.ts",
    exclude: [
      "**/node_modules/**",
      "**/node_modules2/**",
      "**/node_modules_root_owned/**",
      "**/e2e/**",
    ],
    coverage: {
      provider: "v8",
      reporter: ["text", "json", "html"],
      // Phase 5 quality gate: recurring invoices, multi-currency, calendar UI/settings.
      include: [
        "lib/stores/recurring-invoices.ts",
        "lib/stores/currencies.ts",
        "lib/stores/calendar.ts",
        "components/shared/currency-selector.tsx",
        "components/shared/currency-amount.tsx",
        "components/invoices/recurring-invoice-form.tsx",
        "components/calendar/event-form-modal.tsx",
        "components/calendar/event-detail-popover.tsx",
        "app/(dashboard)/invoices/recurring/page.tsx",
        "app/(dashboard)/invoices/recurring/create/page.tsx",
        "app/(dashboard)/invoices/recurring/[id]/page.tsx",
        "app/(dashboard)/invoices/recurring/[id]/edit/page.tsx",
        "app/(dashboard)/settings/currency/page.tsx",
        "app/(dashboard)/settings/calendar/page.tsx",
        "app/(dashboard)/calendar/page.tsx",
        "components/expenses/receipt-upload.tsx",
        "components/portal/payment-form.tsx",
      ],
      thresholds: {
        lines: 80,
        functions: 80,
        branches: 80,
        statements: 80,
      },
      exclude: ["**/*.d.ts", "**/types/**"],
    },
  },
  resolve: {
    alias: {
      "@": path.resolve(__dirname, "./"),
    },
  },
});

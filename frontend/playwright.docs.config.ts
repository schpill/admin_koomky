import { defineConfig, devices } from "@playwright/test";

export default defineConfig({
  testDir: "./scripts/docs",
  testMatch: /capture-screenshots\.ts/,
  fullyParallel: false,
  use: {
    baseURL: process.env.DOCS_SCREENSHOT_BASE_URL ?? "http://127.0.0.1:3000",
    screenshot: "off",
    trace: "off",
  },
  projects: [
    {
      name: "chromium",
      use: { ...devices["Desktop Chrome"] },
    },
  ],
});

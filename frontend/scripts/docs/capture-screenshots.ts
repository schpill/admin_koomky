import { chromium } from "@playwright/test";
import { promises as fs } from "fs";
import path from "path";
import { DOC_MODULES } from "../../lib/docs/config";

const baseURL = process.env.DOCS_SCREENSHOT_BASE_URL ?? "http://127.0.0.1:3000";
const email = process.env.DOCS_SCREENSHOT_EMAIL;
const password = process.env.DOCS_SCREENSHOT_PASSWORD;

async function main() {
  if (!email || !password) {
    console.log(
      "Skipping screenshots: missing DOCS_SCREENSHOT_EMAIL or DOCS_SCREENSHOT_PASSWORD"
    );
    return;
  }

  const browser = await chromium.launch();
  const page = await browser.newPage({
    baseURL,
    viewport: { width: 1600, height: 1000 },
  });

  await page.goto("/auth/login");
  await page.getByLabel(/email/i).fill(email);
  await page.getByLabel(/password/i).fill(password);
  await page.getByRole("button", { name: /login|connexion/i }).click();
  await page.waitForURL((url) => !url.pathname.includes("/auth/login"));

  for (const module of DOC_MODULES) {
    const targetDir = path.join(
      process.cwd(),
      "public",
      "docs",
      "screenshots",
      module.slug
    );
    await fs.mkdir(targetDir, { recursive: true });
    await page.goto(`/docs/${module.slug}`);
    await page.screenshot({
      path: path.join(targetDir, "overview.png"),
      fullPage: false,
    });
    console.log(`Captured ${module.slug}`);
  }

  await browser.close();
}

main().catch((error) => {
  console.error(error);
  process.exitCode = 1;
});

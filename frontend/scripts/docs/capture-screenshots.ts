import { chromium } from "@playwright/test";
import { promises as fs } from "fs";
import path from "path";
import { DOC_MODULES } from "../../lib/docs/config";

const baseURL = process.env.DOCS_SCREENSHOT_BASE_URL ?? "http://127.0.0.1:3000";
const email = process.env.DOCS_SCREENSHOT_EMAIL;
const password = process.env.DOCS_SCREENSHOT_PASSWORD;

async function resolveChromiumExecutablePath() {
  const explicit = process.env.PLAYWRIGHT_CHROMIUM_EXECUTABLE_PATH;
  if (explicit) {
    return explicit;
  }

  for (const candidate of ["/usr/bin/chromium-browser", "/usr/bin/chromium"]) {
    try {
      await fs.access(candidate);
      return candidate;
    } catch {
      continue;
    }
  }

  return undefined;
}

async function main() {
  if (!email || !password) {
    console.log(
      "Skipping screenshots: missing DOCS_SCREENSHOT_EMAIL or DOCS_SCREENSHOT_PASSWORD"
    );
    return;
  }

  const browser = await chromium.launch({
    executablePath: await resolveChromiumExecutablePath(),
  });
  const page = await browser.newPage({
    baseURL,
    viewport: { width: 1600, height: 1000 },
  });

  await page.goto("/auth/login");
  await page.waitForSelector("#email", { state: "visible", timeout: 30000 });
  await page.waitForSelector("#password", { state: "visible", timeout: 30000 });
  await page.locator("#email").fill(email);
  await page.locator("#password").fill(password);
  await page
    .getByRole("button", { name: /se connecter|sign in|connexion/i })
    .click();

  const loginOutcome = await Promise.race([
    page
      .waitForFunction(() => window.location.pathname !== "/auth/login", {
        timeout: 30000,
      })
      .then(() => "authenticated" as const),
    page
      .waitForSelector("#code", { state: "visible", timeout: 30000 })
      .then(() => "two-factor" as const),
  ]).catch(async () => {
    const bodyText = await page.locator("body").innerText().catch(() => "");
    throw new Error(
      `Login did not complete. Stayed on /auth/login. Visible text excerpt: ${bodyText.slice(0, 400)}`
    );
  });

  if (loginOutcome === "two-factor") {
    throw new Error(
      "The account requires 2FA. Use a non-2FA account or extend the screenshot script to handle verification codes."
    );
  }

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

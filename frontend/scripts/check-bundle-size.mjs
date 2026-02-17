#!/usr/bin/env node

import fs from "node:fs";
import path from "node:path";
import zlib from "node:zlib";

const nextStaticDir = path.resolve(process.cwd(), ".next", "static");
const appBuildManifestPath = path.resolve(
  process.cwd(),
  ".next",
  "app-build-manifest.json"
);
const maxJsGzipBytes = Number.parseInt(
  process.env.MAX_JS_GZIP_BYTES ?? `${300 * 1024}`,
  10
);
const maxCssGzipBytes = Number.parseInt(
  process.env.MAX_CSS_GZIP_BYTES ?? `${50 * 1024}`,
  10
);

function collectFiles(dir) {
  if (!fs.existsSync(dir)) {
    return [];
  }

  const entries = fs.readdirSync(dir, { withFileTypes: true });
  const files = [];

  for (const entry of entries) {
    const fullPath = path.join(dir, entry.name);
    if (entry.isDirectory()) {
      files.push(...collectFiles(fullPath));
      continue;
    }

    files.push(fullPath);
  }

  return files;
}

function gzipSize(filePath) {
  const content = fs.readFileSync(filePath);
  return zlib.gzipSync(content).length;
}

function formatKb(bytes) {
  return `${(bytes / 1024).toFixed(2)} KB`;
}

if (!fs.existsSync(nextStaticDir)) {
  console.error("Build output not found. Run `pnpm build` first.");
  process.exit(1);
}

if (!fs.existsSync(appBuildManifestPath)) {
  console.error("App build manifest not found. Run `pnpm build` first.");
  process.exit(1);
}

const files = collectFiles(nextStaticDir);
const jsFiles = files.filter((filePath) => filePath.endsWith(".js"));
const cssFiles = files.filter((filePath) => filePath.endsWith(".css"));

const appBuildManifest = JSON.parse(
  fs.readFileSync(appBuildManifestPath, "utf8")
);
const pageEntries = Object.entries(appBuildManifest.pages ?? {});

const routeBudgets = pageEntries.map(([route, routeFiles]) => {
  const filesForRoute = Array.isArray(routeFiles) ? routeFiles : [];
  const js = filesForRoute
    .filter((filePath) => filePath.endsWith(".js"))
    .reduce((sum, filePath) => {
      const absolutePath = path.resolve(process.cwd(), ".next", filePath);
      return fs.existsSync(absolutePath) ? sum + gzipSize(absolutePath) : sum;
    }, 0);

  const css = filesForRoute
    .filter((filePath) => filePath.endsWith(".css"))
    .reduce((sum, filePath) => {
      const absolutePath = path.resolve(process.cwd(), ".next", filePath);
      return fs.existsSync(absolutePath) ? sum + gzipSize(absolutePath) : sum;
    }, 0);

  return { route, js, css };
});

const heaviestJsRoute = [...routeBudgets].sort((a, b) => b.js - a.js)[0] ?? {
  route: "n/a",
  js: 0,
  css: 0,
};
const heaviestCssRoute = [...routeBudgets].sort((a, b) => b.css - a.css)[0] ?? {
  route: "n/a",
  js: 0,
  css: 0,
};

console.log(
  `Max route JS gzip:  ${formatKb(heaviestJsRoute.js)} (${heaviestJsRoute.route}) (limit ${formatKb(maxJsGzipBytes)})`
);
console.log(
  `Max route CSS gzip: ${formatKb(heaviestCssRoute.css)} (${heaviestCssRoute.route}) (limit ${formatKb(maxCssGzipBytes)})`
);

const topJs = jsFiles
  .map((filePath) => ({ filePath, size: gzipSize(filePath) }))
  .sort((a, b) => b.size - a.size)
  .slice(0, 5);

const topCss = cssFiles
  .map((filePath) => ({ filePath, size: gzipSize(filePath) }))
  .sort((a, b) => b.size - a.size)
  .slice(0, 5);

if (topJs.length > 0) {
  console.log("Top JS files:");
  for (const file of topJs) {
    console.log(
      `  - ${path.relative(process.cwd(), file.filePath)}: ${formatKb(file.size)}`
    );
  }
}

if (topCss.length > 0) {
  console.log("Top CSS files:");
  for (const file of topCss) {
    console.log(
      `  - ${path.relative(process.cwd(), file.filePath)}: ${formatKb(file.size)}`
    );
  }
}

if (
  heaviestJsRoute.js > maxJsGzipBytes ||
  heaviestCssRoute.css > maxCssGzipBytes
) {
  console.error("Bundle budget exceeded.");
  process.exit(1);
}

console.log("Bundle budget check passed.");
